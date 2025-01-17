/*
 * Copyright 2020 Google LLC
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
import styled from 'styled-components';
import {
  useCallback,
  useLayoutEffect,
  useRef,
  useState,
  memo,
} from '@googleforcreators/react';
import { useUnits } from '@googleforcreators/units';
import { useTransformHandler } from '@googleforcreators/transform';
import PropTypes from 'prop-types';
import { getDefinitionForType } from '@googleforcreators/elements';
import {
  elementWithPosition,
  elementWithSize,
  elementWithRotation,
} from '@googleforcreators/element-library';
import {
  FrameWithMask as WithMask,
  getElementMask,
  MaskTypes,
} from '@googleforcreators/masks';
import {
  usePerformanceTracking,
  TRACKING_EVENTS,
} from '@googleforcreators/design-system';

/**
 * Internal dependencies
 */
import {
  useStory,
  useTransform,
  useCanvas,
  useConfig,
  useDropTargets,
} from '../../app';
import WithLink from '../elementLink/frame';
import useDoubleClick from '../../utils/useDoubleClick';

// @todo: should the frame borders follow clip lines?

// Pointer events are disabled in the display mode to ensure that selection
// can be limited to the mask.
const Wrapper = styled.div`
  ${elementWithPosition}
  ${elementWithSize}
	${elementWithRotation}
  pointer-events: ${({ maskDisabled }) => (maskDisabled ? 'initial' : 'none')};

  outline: 1px solid transparent;
  transition: outline-color 0.5s;

  &:focus,
  &:active,
  &:hover {
    outline-color: ${({ theme, hasMask }) =>
      hasMask ? 'transparent' : theme.colors.border.selection};
  }
`;

const EmptyFrame = styled.div`
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  pointer-events: none;
`;

const NOOP = () => {};

function FrameElement({ id }) {
  const [isTransforming, setIsTransforming] = useState(false);

  const {
    setNodeForElement,
    handleSelectElement,
    isEditing,
    setEditingElement,
    setEditingElementWithState,
  } = useCanvas(({ state, actions }) => ({
    setNodeForElement: actions.setNodeForElement,
    handleSelectElement: actions.handleSelectElement,
    isEditing: state.isEditing,
    setEditingElement: actions.setEditingElement,
    setEditingElementWithState: actions.setEditingElementWithState,
  }));
  const { isSelected, isOnlySelectedElement, isActive, isBackground, element } =
    useStory(({ state }) => {
      const isSelected = state.selectedElementIds.includes(id);
      const isOnlySelectedElement =
        isSelected && state.selectedElementIds.length === 1;
      const isActive = isOnlySelectedElement && !isTransforming && !isEditing;

      return {
        isSelected,
        isBackground: state.currentPage?.elements[0].id === id,
        element: state.currentPage?.elements.find((el) => el.id === id),
        isOnlySelectedElement,
        isActive,
      };
    });
  const { type, flip } = element;
  const { Frame, isMaskable, Controls } = getDefinitionForType(type);
  const elementRef = useRef();
  const [hovering, setHovering] = useState(false);
  const { isRTL, styleConstants: { topOffset } = {} } = useConfig();

  const {
    draggingResource,
    activeDropTargetId,
    isDropSource,
    registerDropTarget,
    unregisterDropTarget,
  } = useDropTargets(
    ({
      state: { draggingResource, activeDropTargetId },
      actions: { isDropSource, registerDropTarget, unregisterDropTarget },
    }) => ({
      draggingResource,
      activeDropTargetId,
      isDropSource,
      registerDropTarget,
      unregisterDropTarget,
    })
  );

  const onPointerEnter = () => setHovering(true);
  const onPointerLeave = () => setHovering(false);

  const isLinkActive = useTransform(
    ({ state }) => !isSelected && hovering && !state.isAnythingTransforming
  );

  const getBox = useUnits(({ actions }) => actions.getBox);

  useLayoutEffect(() => {
    setNodeForElement(id, elementRef.current);
  }, [id, setNodeForElement]);
  const box = getBox(element);

  useTransformHandler(id, (transform) => {
    const target = elementRef.current;
    if (transform?.dropTargets?.hover !== undefined) {
      target.style.opacity = transform.dropTargets.hover ? 0 : 1;
    }
    setIsTransforming(transform !== null);
  });

  // Media needs separate handler for double click.
  const { isMedia } = getDefinitionForType(type);
  const handleMediaDoubleClick = useCallback(
    (evt) => {
      if (!isSelected) {
        handleSelectElement(id, evt);
      }
      setEditingElement(id);
    },
    [id, setEditingElement, handleSelectElement, isSelected]
  );
  const handleMediaClick = useDoubleClick(NOOP, handleMediaDoubleClick);

  // For elements with no mask, handle events by the wrapper.
  const mask = getElementMask(element);
  const maskDisabled =
    !mask?.type || (isBackground && mask.type !== MaskTypes.RECTANGLE);
  const eventHandlers = {
    onMouseDown: (evt) => {
      if (!isSelected) {
        handleSelectElement(id, evt);
      }
      elementRef.current.focus({ preventScroll: true });
      if (!isBackground) {
        evt.stopPropagation();
      }
    },
    onFocus: (evt) => {
      if (!isSelected) {
        handleSelectElement(id, evt);
      }
    },
    onPointerEnter,
    onPointerLeave,
    onClick: isMedia ? handleMediaClick(id) : null,
  };

  usePerformanceTracking({
    node: maskDisabled ? elementRef.current : null,
    eventData: {
      ...TRACKING_EVENTS.SELECT_ELEMENT,
      label: element.type,
    },
    eventType: 'pointerdown',
  });

  return (
    <WithLink element={element} active={isLinkActive} anchorRef={elementRef}>
      {Controls && (
        <Controls
          isTransforming={isTransforming}
          box={box}
          elementRef={elementRef}
          element={element}
          isRTL={isRTL}
          topOffset={topOffset}
          isActive={isActive}
        />
      )}
      <Wrapper
        ref={elementRef}
        data-element-id={id}
        {...box}
        // eslint-disable-next-line styled-components-a11y/no-noninteractive-tabindex -- Needed for being able to focus on the selected element on canvas, e.g. for entering edit mode.
        tabIndex={0}
        aria-labelledby={`layer-${id}`}
        hasMask={isMaskable}
        data-testid="frameElement"
        maskDisabled={maskDisabled}
        {...(maskDisabled ? eventHandlers : null)}
      >
        <WithMask
          element={element}
          fill
          flip={flip}
          eventHandlers={!maskDisabled ? eventHandlers : null}
          draggingResource={draggingResource}
          activeDropTargetId={activeDropTargetId}
          isDropSource={isDropSource}
          registerDropTarget={registerDropTarget}
          unregisterDropTarget={unregisterDropTarget}
        >
          {Frame ? (
            <Frame
              wrapperRef={elementRef}
              element={element}
              box={box}
              isOnlySelectedElement={isOnlySelectedElement}
              setEditingElementWithState={setEditingElementWithState}
            />
          ) : (
            <EmptyFrame />
          )}
        </WithMask>
      </Wrapper>
    </WithLink>
  );
}

FrameElement.propTypes = {
  id: PropTypes.string.isRequired,
};

export default memo(FrameElement);
