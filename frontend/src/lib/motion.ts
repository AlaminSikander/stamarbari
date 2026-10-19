export const ease = {
  out: [0.22, 1, 0.36, 1] as [number, number, number, number],
  inOut: [0.77, 0, 0.18, 1] as [number, number, number, number],
  soft: [0.4, 0, 0.2, 1] as [number, number, number, number],
};

export const duration = {
  fast: 0.3,
  base: 0.6,
  slow: 0.9,
  cinematic: 1.2,
};

export const stagger = 0.08;

export const viewportOnce = { once: true, margin: "0px 0px -12% 0px" } as const;

/** Delay (s) after which the homepage hero sequence has finished its main beats. */
export const heroSequenceEnd = 1.5;
