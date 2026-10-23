// Starts the whole app with one command: the Next.js site on :3000 and the Laravel
// app it proxies /admin and the API to. Open http://localhost:3000 and
// http://localhost:3000/admin. Stop everything with Ctrl+C.
import { spawn } from "node:child_process";
import { fileURLToPath } from "node:url";
import path from "node:path";

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), "..");
const backendDir = path.resolve(root, "../backend");
const backend = new URL(process.env.BACKEND_ORIGIN || "http://127.0.0.1:8000");
const env = { ...process.env, BACKEND_ORIGIN: backend.origin };
if (process.env.BACKEND_ORIGIN && !process.env.NEXT_PUBLIC_API_URL) {
  env.NEXT_PUBLIC_API_URL = `${backend.origin.replace("127.0.0.1", "localhost")}/api/v1`;
}

const processes = [
  {
    name: "laravel",
    color: "\x1b[35m",
    cmd: "php",
    // --tries=0: fail if the port is taken instead of silently moving to another port.
    args: ["artisan", "serve", `--host=${backend.hostname}`, `--port=${backend.port || 80}`, "--tries=0"],
    cwd: backendDir,
  },
  { name: "queue", color: "\x1b[33m", cmd: "php", args: ["artisan", "queue:work"], cwd: backendDir },
  { name: "next", color: "\x1b[36m", cmd: "npx", args: ["next", "dev", ...process.argv.slice(2)], cwd: root },
];

let stopping = false;
const children = processes.map(({ name, color, cmd, args, cwd }) => {
  // Own process group, so stopping it also stops the servers it starts (php -S, next).
  const child = spawn(cmd, args, { cwd, env, stdio: ["ignore", "pipe", "pipe"], detached: true });
  const prefix = `${color}[${name}]\x1b[0m `;
  const pipe = (stream, out) =>
    stream.on("data", (chunk) => {
      for (const line of chunk.toString().split(/\r?\n/)) if (line) out.write(prefix + line + "\n");
    });
  pipe(child.stdout, process.stdout);
  pipe(child.stderr, process.stderr);
  child.on("exit", (code) => {
    if (!stopping) {
      console.error(`${prefix}exited with code ${code}; stopping the others.`);
      stop(code ?? 1);
    }
  });
  return child;
});

function stop(code = 0) {
  if (stopping) return;
  stopping = true;
  for (const child of children) {
    try {
      process.kill(-child.pid, "SIGTERM");
    } catch {
      // Already gone.
    }
  }
  setTimeout(() => process.exit(code), 800);
}

process.on("SIGINT", () => stop(0));
process.on("SIGTERM", () => stop(0));
process.on("exit", () => {
  for (const child of children) {
    try {
      process.kill(-child.pid, "SIGKILL");
    } catch {
      // Already gone.
    }
  }
});
