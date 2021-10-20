/*
 * Copyright 2021 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     https://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

/**
 * External dependencies
 */
import {
  clickButton,
  disableCheckbox,
  enableCheckbox,
  visitSettings,
} from '@web-stories-wp/e2e-test-utils';

/**
 * Internal dependencies
 */
import {
  telemetryCheckboxSelector,
  videoCacheCheckboxSelector,
  videoOptimizationCheckboxSelector,
} from '../../../utils';

describe('Admin User', () => {
  beforeEach(async () => {
    await visitSettings();

    await enableCheckbox(telemetryCheckboxSelector);
    await enableCheckbox(videoOptimizationCheckboxSelector);
    await enableCheckbox(videoCacheCheckboxSelector);
  });

  afterEach(async () => {
    await visitSettings();

    await enableCheckbox(telemetryCheckboxSelector);
    await enableCheckbox(videoOptimizationCheckboxSelector);
    await enableCheckbox(videoCacheCheckboxSelector);
  });

  it('should let me see and update the telemetry checkbox', async () => {
    await disableCheckbox(telemetryCheckboxSelector);

    await expect(page).toMatchElement(
      `${telemetryCheckboxSelector}:not(:checked)`
    );
  });

  it('should let me see and update the video optimization checkbox', async () => {
    await disableCheckbox(videoOptimizationCheckboxSelector);

    await expect(page).toMatchElement(
      `${videoOptimizationCheckboxSelector}:not(:checked)`
    );
  });

  it('should let me see and update the video cache checkbox', async () => {
    await disableCheckbox(videoCacheCheckboxSelector);

    await expect(page).toMatchElement(
      `${videoCacheCheckboxSelector}:not(:checked)`
    );
  });

  it('should let me see and update the monetization settings', async () => {
    await clickButton('button[aria-label="Monetization type"]', {
      text: 'None',
    });

    await expect(page).toClick('button', { text: 'Google AdSense' });

    await expect(page).toMatchElement(
      'button[aria-label="Monetization type"]',
      { text: 'Google AdSense' }
    );
  });
});
