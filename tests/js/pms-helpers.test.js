import { describe, expect, it } from 'vitest';
import { boundedProgress } from '../../public/assets/js/pms-helpers.js';
describe('project preview progress', () => {
  it.each([[25.5,25.5],['75',75],[125,100],[-10,0],['invalid',0],[Infinity,0],[null,0]])('bounds %s to %s', (input, expected) => {
    expect(boundedProgress(input)).toBe(expected);
  });
});
