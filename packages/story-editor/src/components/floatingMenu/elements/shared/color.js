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
import { PLACEMENT } from '@googleforcreators/design-system';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import Color from '../../../form/color';

const EYEDROPPER_WIDTH = 38; // icon width + spacing
const WIDTH_INCLUDING_INPUTS = 178;
const WIDTH_EXCLUDING_INPUTS = 60;

const PICKER_MAX_HEIGHT = 362;

function getWidth(hasInputs, hasEyedropper) {
  return (
    (hasInputs ? WIDTH_INCLUDING_INPUTS : WIDTH_EXCLUDING_INPUTS) +
    (hasEyedropper ? EYEDROPPER_WIDTH : 0)
  );
}

function FloatingColor(props) {
  const { hasInputs, hasEyedropper, allowsGradient = true } = props;
  const width = getWidth(hasInputs, hasEyedropper);
  return (
    <Color
      width={width}
      maxHeight={PICKER_MAX_HEIGHT}
      pickerPlacement={PLACEMENT.TOP_START}
      isInDesignMenu
      shouldCloseOnSelection
      allowsGradient={allowsGradient}
      allowsSavedColors
      allowsSavedColorDeletion={false}
      pickerHasEyedropper={!hasEyedropper}
      {...props}
    />
  );
}

FloatingColor.propTypes = {
  hasInputs: PropTypes.bool,
  hasEyedropper: PropTypes.bool,
  allowsGradient: PropTypes.bool,
};

export default FloatingColor;
