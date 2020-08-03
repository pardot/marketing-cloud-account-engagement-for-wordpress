import {__} from '@wordpress/i18n';
import {Component} from '@wordpress/element';
import {InspectorControls} from '@wordpress/block-editor';
import ServerSideRender from '@wordpress/server-side-render';

import '../editor.scss';

class DynamicContentEdit extends Component {

    constructor() {
        super(...arguments);
        this.state = {
            dropdownItems: [],
            dcItems: [],
            showDropdown: false,
            interactive: false,
        };

        // This binding is necessary to make `this` work in the functions
        this.render = this.render.bind(this);
        this.hideOverlay = this.hideOverlay.bind(this);
    }

    componentDidMount() {

        // We are using the pre-existing PHP Ajax function to allow us to obtain the user's dynamic content.
        // We need to parse the return value, as the response comes backs as HTML (<select><option>...).
        let xhr = new XMLHttpRequest()

        xhr.addEventListener('load', () => {
            let shortcodes = [...xhr.responseText.matchAll(/\[.*?id=&quot;(.*?)&quot; default=&quot;(.*?)&quot;]">(.*?)</g)]
            let dropdownItems = [];
            let dcItems = []
            for (let i = 0; i < shortcodes.length; i++) {
                dropdownItems.push({dynamicContent_id: shortcodes[i][1], title: shortcodes[i][3]})
                dcItems[shortcodes[i][1]] = shortcodes[i][2];
            }

            if (shortcodes.length > 0) {
                this.setState({
                    dropdownItems: dropdownItems,
                    showDropdown: true,
                    dcItems: dcItems,
                });
            }

        })

        xhr.open('POST', ajaxurl)
        let formData = new FormData();
        formData.append("action", "get_pardot_dynamicContent_shortcode_select_html");
        xhr.send(formData);
    }

    // We need getDerivedStateFromProps() and hideOverlay() to allow the user to click anywhere on the block to select it.
    // I believe the iframe causes issues with the way the Block Editor interacts with user clicks.
    // We essentially need to create an overlay to capture the first click, then switch to interactive mode,
    // which allows the user to interact with the iframe.
    // Source: https://github.com/WordPress/gutenberg/blob/master/packages/block-library/src/embed/embed-preview.js#L34
    static getDerivedStateFromProps(nextProps, state) {
        if (!nextProps.isSelected && state.interactive) {
            return {interactive: false};
        }

        return null;
    }

    hideOverlay() {
        this.setState({interactive: true});
    }

    handleValueOnChange(e) {
        this.props.setAttributes({
            [e.target.id]: e.target.value
        });
    }

    handleDropdownChange(e) {
        let dynamicContent_default = this.state.dcItems[e.target.value];
        this.props.setAttributes({dynamicContent_id: e.target.value, dynamicContent_default: dynamicContent_default});
    };


    render() {
        return (
            <>
                <InspectorControls>
                    <div className="sidebar">

                        <h2>{__('Dynamic Content')}</h2>
                        {this.state.showDropdown ?
                            <select name="dynamicContentSelect" value={this.props.attributes.dynamicContent_id}
                                    onChange={e => this.handleDropdownChange(e)}>
                                <option value={-1} key={-1} label={"Select"}>Select</option>
                                {this.state.dropdownItems.map(function (n) {
                                    return (<option label={n.title} value={n.dynamicContent_id}
                                                    key={n.dynamicContent_id}>{n.title}</option>);
                                })}
                            </select> : <p>It doesn't appear that you have any dynamic content.</p>}

                        <h2>{__('Width')}</h2>
                        <input type="text" className="textEntry" id="width"
                               value={this.props.attributes.width}
                               onChange={(e) => this.handleValueOnChange(e)}
                               placeholder={__('Must be an integer (e.g. 250)')}/>

                        <h2>{__('Height')}</h2>
                        <input type="text" className="textEntry" id="height"
                               value={this.props.attributes.height}
                               onChange={(e) => this.handleValueOnChange(e)}
                               placeholder={__('Must be an integer (e.g. 250)')}/>
                    </div>
                </InspectorControls>

                {this.props.attributes.dynamicContent_id ?
                    <ServerSideRender
                        block="pardot/dynamic-content"
                        attributes={this.props.attributes}
                    /> : <h5>Select dynamic content in the block settings sidebar.</h5>}
                {!this.state.interactive && (<div className="overlay" onMouseUp={this.hideOverlay}/>)}
            </>
        );
    }
}

export default DynamicContentEdit;
