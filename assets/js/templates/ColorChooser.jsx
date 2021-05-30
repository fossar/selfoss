import React from 'react';
import PropTypes from 'prop-types';

export default function ColorChooser({tag, onChange}) {
    const colorChooser = React.useRef(null);

    React.useLayoutEffect(() => {
        // init colorpicker
        const picker = colorChooser.current;
        $(picker).spectrum({
            showPaletteOnly: true,
            color: tag.color,
            palette: [
                ['#ffccc9', '#ffce93', '#fffc9e', '#ffffc7', '#9aff99', '#96fffb', '#cdffff', '#cbcefb', '#fffe65', '#cfcfcf', '#fd6864', '#fe996b', '#fcff2f', '#67fd9a', '#38fff8', '#68fdff', '#9698ed', '#c0c0c0', '#fe0000', '#f8a102', '#ffcc67', '#f8ff00', '#34ff34', '#68cbd0', '#34cdf9', '#6665cd', '#9b9b9b', '#cb0000', '#f56b00', '#ffcb2f', '#ffc702', '#32cb00', '#00d2cb', '#3166ff', '#6434fc', '#656565', '#9a0000', '#ce6301', '#cd9934', '#999903', '#009901', '#329a9d', '#3531ff', '#6200c9', '#343434', '#680100', '#963400', '#986536', '#646809', '#036400', '#34696d', '#00009b', '#303498', '#000000', '#330001', '#643403', '#663234', '#343300', '#013300', '#003532', '#010066', '#340096']
            ],
            change: onChange,
        });

        return () => {
            $(picker).spectrum('destroy');
        };
    }, [onChange, tag.color]);

    const style = React.useMemo(
        () => ({ backgroundColor: tag.color }),
        [tag.color]
    );

    return (
        <span className="color" style={style} ref={colorChooser} />
    );
}

ColorChooser.propTypes = {
    tag: PropTypes.object.isRequired,
    onChange: PropTypes.func.isRequired,
};
