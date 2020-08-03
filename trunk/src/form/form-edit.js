import {__} from '@wordpress/i18n';
import {Component} from '@wordpress/element';
import {InspectorControls} from '@wordpress/block-editor';
import ServerSideRender from '@wordpress/server-side-render';


import '../editor.scss';

class FormEdit extends Component {

    constructor() {
        super(...arguments);
        this.state = {
            dropdownItems: [],
            showDropdown: false,
            interactive: false,
        };

        // This binding is necessary to make `this` work in the functions
        this.render = this.render.bind(this);
        this.hideOverlay = this.hideOverlay.bind(this);
    }

    componentDidMount() {

        // We are using the pre-existing PHP Ajax function to allow us to obtain the user's forms.
        // We need to parse the return value, as the response comes backs as HTML (<select><option>...).
        let xhr = new XMLHttpRequest()

        xhr.addEventListener('load', () => {
            let shortcodes = [...xhr.responseText.matchAll(/"\[.*?]"/g)]
            let dropdownItems = [];
            for (let i = 0; i < shortcodes.length; i++) {
                let parsedShortcode = [...shortcodes[i][0].matchAll(/ (.*?)=&quot;(.*?)&quot;/g)];
                dropdownItems.push({form_id: parsedShortcode[0][2], title: parsedShortcode[1][2]})
            }

            if (shortcodes.length > 0) {
                this.setState({
                    dropdownItems: dropdownItems,
                    showDropdown: true,
                });
            }

        })

        xhr.open('POST', ajaxurl)
        let formData = new FormData();
        formData.append("action", "get_pardot_forms_shortcode_select_html");
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
        let index = e.target.selectedIndex;
        let title = e.target[index].text
        this.props.setAttributes({form_id: e.target.value, title: title});
    };


    render() {
        return (
            <>
                <InspectorControls>
                    <div className="sidebar">

                        <h2>{__('Form')}</h2>
                        {this.state.showDropdown ? <select name="formSelect" value={this.props.attributes.form_id}
                                                           onChange={e => this.handleDropdownChange(e)}>
                            <option value={-1} key={-1} label={"Select"}>Select</option>
                            {this.state.dropdownItems.map(function (n) {
                                return (<option label={n.title} value={n.form_id} key={n.form_id}>{n.title}</option>);
                            })}
                        </select> : <p>It doesn't appear that you have any forms.</p>}

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

                {this.props.attributes.form_id ?
                <ServerSideRender
                    block="pardot/form"
                    attributes={this.props.attributes}
                /> : <h5>Select a form in the block settings sidebar.</h5>}
                {!this.state.interactive && (<div className="overlay" onMouseUp={this.hideOverlay}/>)}
            </>
        );
    }
}

export default FormEdit;
