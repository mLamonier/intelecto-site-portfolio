let bar: HTMLDivElement | null = null;
let active = 0;

const ensureBar = () => {
  if (bar) return bar;
  const el = document.createElement("div");
  el.id = "global-loading-bar";
  document.body.appendChild(el);
  bar = el;
  return el;
};

export const startLoading = () => {
  const el = ensureBar();
  active += 1;
  requestAnimationFrame(() => {
    el.style.opacity = "1";
    el.style.width = "80%";
  });
};

export const stopLoading = () => {
  if (active > 0) active -= 1;
  if (active > 0 || !bar) return;

  bar.style.width = "100%";
  setTimeout(() => {
    if (!bar) return;
    bar.style.opacity = "0";
    setTimeout(() => {
      if (bar) {
        bar.style.width = "0%";
      }
    }, 200);
  }, 200);
};

export const resetLoadingBar = () => {
  active = 0;
  if (bar) {
    bar.style.width = "0%";
    bar.style.opacity = "0";
  }
};
