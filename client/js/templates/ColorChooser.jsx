import React, { useContext, useMemo } from 'react';
import { useFloating, autoUpdate, flip, offset, shift } from '@floating-ui/react-dom';
import { FloatingPortal } from '@floating-ui/react-dom-interactions';
import PropTypes from 'prop-types';
import { Button as MenuButton, Wrapper as MenuWrapper, Menu, MenuItem } from 'react-aria-menubutton';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { colorByBrightness } from '../helpers/color';
import { LocalizationContext } from '../helpers/i18n';
import * as icons from '../icons';

const palette = ['#ffccc9', '#ffce93', '#fffc9e', '#ffffc7', '#9aff99', '#96fffb', '#cdffff', '#cbcefb', '#fffe65', '#cfcfcf', '#fd6864', '#fe996b', '#fcff2f', '#67fd9a', '#38fff8', '#68fdff', '#9698ed', '#c0c0c0', '#fe0000', '#f8a102', '#ffcc67', '#f8ff00', '#34ff34', '#68cbd0', '#34cdf9', '#6665cd', '#9b9b9b', '#cb0000', '#f56b00', '#ffcb2f', '#ffc702', '#32cb00', '#00d2cb', '#3166ff', '#6434fc', '#656565', '#9a0000', '#ce6301', '#cd9934', '#999903', '#009901', '#329a9d', '#3531ff', '#6200c9', '#343434', '#680100', '#963400', '#986536', '#646809', '#036400', '#34696d', '#00009b', '#303498', '#000000', '#330001', '#643403', '#663234', '#343300', '#013300', '#003532', '#010066', '#340096'];

function ColorButton({tag, color}) {
    const style = useMemo(
        () => ({
            backgroundColor: color,
            color: colorByBrightness(color),
        }),
        [color]
    );

    const selected = color === tag.color;

    return (
        <MenuItem
            className="popup-menu-item"
            value={color}
            style={style}
        >
            {selected
                ? <FontAwesomeIcon icon={icons.check} />
                : ' '
            }
        </MenuItem>
    );
}

ColorButton.propTypes = {
    tag: PropTypes.object.isRequired,
    color: PropTypes.string.isRequired,
};

const preventDefault = (event) => {
    event.preventDefault();
    // Prevent closing navigation on mobile.
    event.stopPropagation();
};

export default function ColorChooser({tag, onChange}) {
    const style = useMemo(
        () => ({ backgroundColor: tag.color }),
        [tag.color]
    );

    const {
        x: menuX,
        y: menuY,
        reference: buttonRef,
        floating: floatingRef,
        strategy: positionStrategy,
    } = useFloating({
        placement: 'right-start',
        strategy: 'fixed',
        middleware: [
            offset({ mainAxis: 10 }),
            shift(),
            flip(),
        ],
        whileElementsMounted: autoUpdate,
    });

    const _ = useContext(LocalizationContext);

    return (
        <MenuWrapper
            className="popup-menu-wrapper color"
            onSelection={onChange}
            onClick={preventDefault}
        >
            <MenuButton
                className="color-chooser-button"
                ref={buttonRef}
                title={_('tag_change_color_button_title')}
            >
                <span
                    className="color-box"
                    style={style}
                />
                <span className="visually-hidden">
                    {_('tag_change_color_button_title')}
                </span>
            </MenuButton>
            <FloatingPortal>
                <Menu
                    className="color-chooser popup-menu"
                    ref={floatingRef}
                    style={{
                        position: positionStrategy,
                        top: menuY ?? '',
                        left: menuX ?? '',
                    }}
                >
                    {palette.map((color) => (
                        <ColorButton
                            key={color}
                            color={color}
                            tag={tag}
                        />
                    ))}
                    {!palette.includes(tag.color) && (
                        <ColorButton
                            key="custom"
                            color={tag.color}
                            tag={tag}
                        />
                    )}
                </Menu>
            </FloatingPortal>
        </MenuWrapper>
    );
}

ColorChooser.propTypes = {
    tag: PropTypes.object.isRequired,
    onChange: PropTypes.func.isRequired,
};
