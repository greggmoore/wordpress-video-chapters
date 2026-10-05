const test = require('node:test');
const assert = require('node:assert/strict');
const { canSeek, activeIndex } = require('../assets/chapter-state.js');
test('chapter selection respects exact boundaries and gaps', () => {
  assert.equal(activeIndex([10, 60, 120], 0), -1);
  assert.equal(activeIndex([10, 60, 120], 59.99), 0);
  assert.equal(activeIndex([10, 60, 120], 60), 1);
  assert.equal(activeIndex([10, 60, 120], 200), 2);
  assert.equal(activeIndex([], 5), -1);
  assert.equal(activeIndex([0], NaN), -1);
});
test('unloaded, live, and out-of-range media cannot be sought', () => {
  assert.equal(canSeek(0, 120), true);
  assert.equal(canSeek(119.99, 120), true);
  for (const [start, duration] of [[120,120], [-1,120], [0,0], [0,NaN], [0,Infinity], [NaN,120]]) {
    assert.equal(canSeek(start, duration), false);
  }
});
