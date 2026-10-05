/* Pure state helpers shared by the browser controller and boundary tests. */
(function (root) {
  'use strict';
  const state = {
    canSeek(start, duration) {
      return Number.isFinite(start) && start >= 0 && Number.isFinite(duration) && duration > 0 && start < duration;
    },
    activeIndex(starts, time) {
      if (!Number.isFinite(time) || time < 0) return -1;
      let active = -1;
      starts.forEach((start, index) => { if (start <= time) active = index; });
      return active;
    }
  };
  if (typeof module === 'object' && module.exports) module.exports = state;
  else root.BlumooChapterState = state;
})(typeof window === 'undefined' ? globalThis : window);
