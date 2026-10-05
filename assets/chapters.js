(function () {
  'use strict';
  document.querySelectorAll('.blumoo-chapters').forEach(component => {
    const video = component.querySelector('video');
    const buttons = [...component.querySelectorAll('button[data-start]')];
    const status = component.querySelector('.blumoo-status');
    const starts = buttons.map(button => Number(button.dataset.start));
    const state = window.BlumooChapterState;
    const refresh = () => {
      const active = state.activeIndex(starts, video.currentTime);
      buttons.forEach((button, index) => {
        button.disabled = Boolean(video.error) || video.readyState < 1 || !state.canSeek(starts[index], video.duration);
        if (index === active && !button.disabled) button.setAttribute('aria-current', 'true');
        else button.removeAttribute('aria-current');
      });
    };
    buttons.forEach((button, index) => button.addEventListener('click', () => {
      if (!state.canSeek(starts[index], video.duration)) return;
      try {
        video.currentTime = starts[index]; // Seeking deliberately preserves the playback state.
        status.textContent = button.textContent.trim();
        refresh();
      } catch (_) { status.textContent = 'This chapter is not available yet.'; }
    }));
    ['loadedmetadata', 'durationchange', 'timeupdate', 'emptied'].forEach(event => video.addEventListener(event, refresh));
    video.addEventListener('error', () => {
      status.textContent = 'The video could not be loaded. Chapter navigation is unavailable.';
      refresh();
    });
    refresh();
  });
})();
