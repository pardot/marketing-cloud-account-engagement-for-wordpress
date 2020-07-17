/**
 * Retrieves the translation of text.
 *
 * @see https://developer.wordpress.org/block-editor/packages/packages-i18n/
 */
import { __ } from '@wordpress/i18n';
import {Component, render} from '@wordpress/element';

/**
 * Lets webpack process CSS, SASS or SCSS files referenced in JavaScript files.
 * Those files can contain any CSS code that gets applied to the editor.
 *
 * @see https://www.npmjs.com/package/@wordpress/scripts#using-css
 */
import './editor.scss';

/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
 *
 * @see https://developer.wordpress.org/block-editor/developers/block-api/block-edit-save/#edit
 *
 * @param {Object} [props]           Properties passed from the editor.
 * @param {string} [props.className] Class name generated for the block.
 *
 * @return {WPElement} Element to render.
 */

class FormEdit extends Component {

	constructor() {
		super();
		this.state = {
			formHTML: '',
			width: null,
			height: null,
		};
	}

	componentDidMount() {
		var xhr = new XMLHttpRequest()

		xhr.addEventListener('load', () => {
			console.log(xhr.responseText);
			this.setState({formHTML: xhr.responseText})
		})

		xhr.open('POST', ajaxurl)
		let formData = new FormData();
		formData.append("action", "get_pardot_forms_shortcode_select_html");
		xhr.send(formData);
	}

	embedForm() {
		let formDropdown = document.getElementById("formshortcode");
		let formID = (formDropdown.options[formDropdown.selectedIndex].value);
		console.log(formID);
	}


	render () {
		return(
			<div className="block">
				<label>{ __('Form') }</label>
				<div id="formList" dangerouslySetInnerHTML={{__html: this.state.formHTML}}>
				</div>
				<div><label>{ __('Width') }</label> <input type="text" className="textEntry" id="width" placeholder={ __('Must be an integer (e.g. 250)') }/></div>
				<div><label>{ __('Height') }</label> <input type="text" className="textEntry" id="height" placeholder={ __('Must be an integer (e.g. 250)') }/></div>
				<button onClick={this.embedForm}>Embed</button>
			</div>
		);
	}
}

export default FormEdit;
