import Image from "next/image";

const decide = `try{var d=document.documentElement;if(sessionStorage.getItem('sab-intro')||matchMedia('(prefers-reduced-motion: reduce)').matches){d.classList.add('intro-skip')}else{sessionStorage.setItem('sab-intro','1');d.classList.add('intro-play')}}catch(e){document.documentElement.classList.add('intro-skip')}`;

/** First-visit brand reveal (~1.2s). Pure CSS so it never waits on hydration. */
export function Preloader() {
  return (
    <>
      <script dangerouslySetInnerHTML={{ __html: decide }} />
      <div className="preloader" aria-hidden>
        <div className="preloader-inner">
          <Image src="/brand/logo.jpg" alt="" width={120} height={132} className="preloader-logo" preload />
          <span className="preloader-line" />
          <span className="preloader-word">Saint Martin Island</span>
        </div>
      </div>
    </>
  );
}
