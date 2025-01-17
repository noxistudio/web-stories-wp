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
import { Icons } from '@googleforcreators/design-system';
import { __ } from '@googleforcreators/i18n';
import { trackEvent } from '@googleforcreators/tracking';

/**
 * Internal dependencies
 */
import { useStory } from '../../../app';
import { MIN_MAX } from '../../panels/design/sizePosition/opacity';
import { Input, useProperties } from './shared';

function LayerOpacity() {
  const { opacity, type } = useProperties(['opacity', 'type']);
  const updateSelectedElements = useStory(
    (state) => state.actions.updateSelectedElements
  );

  const handleOpacityChange = (_, value) => {
    updateSelectedElements({
      properties: () => ({
        opacity: value ?? 100,
      }),
    });

    trackEvent('floating_menu', {
      name: 'set_opacity',
      element: type,
    });
  };

  return (
    <Input
      suffix={<Icons.ColorDrop />}
      value={opacity || 0}
      aria-label={__('Opacity in percent', 'web-stories')}
      onChange={handleOpacityChange}
      min={MIN_MAX.OPACITY.MIN}
      max={MIN_MAX.OPACITY.MAX}
    />
  );
}

export default LayerOpacity;
