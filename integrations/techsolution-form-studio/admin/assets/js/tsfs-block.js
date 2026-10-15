/**
 * "Form Studio Form" block for the block editor. Renders on the server.
 *
 * @package TechSolution_Form_Studio
 */
(function (wp) {
    'use strict';

    var el = wp.element.createElement;
    var __ = wp.i18n.__;
    var InspectorControls = wp.blockEditor.InspectorControls;
    var useBlockProps = wp.blockEditor.useBlockProps;
    var PanelBody = wp.components.PanelBody;
    var SelectControl = wp.components.SelectControl;
    var ToggleControl = wp.components.ToggleControl;
    var Placeholder = wp.components.Placeholder;
    var ServerSideRender = wp.serverSideRender;
    var forms = (window.tsfsBlockData && window.tsfsBlockData.forms) || [];

    function formOptions() {
        return [{ value: 0, label: __('Select a form', 'techsolution-form-studio') }].concat(forms);
    }

    wp.blocks.registerBlockType('techsolution-form-studio/form', {
        apiVersion: 3,
        title: __('Form Studio Form', 'techsolution-form-studio'),
        description: __('Display a form created with TechSolution Form Studio.', 'techsolution-form-studio'),
        category: 'widgets',
        icon: 'feedback',
        keywords: [__('form', 'techsolution-form-studio'), __('contact', 'techsolution-form-studio')],
        attributes: {
            formId: { type: 'number', default: 0 },
            showTitle: { type: 'boolean', default: true }
        },
        edit: function (props) {
            var attributes = props.attributes;
            var blockProps = useBlockProps();
            var picker = el(SelectControl, {
                label: __('Form', 'techsolution-form-studio'),
                value: attributes.formId,
                options: formOptions(),
                onChange: function (value) { props.setAttributes({ formId: parseInt(value, 10) || 0 }); }
            });

            var inspector = el(InspectorControls, {},
                el(PanelBody, { title: __('Form settings', 'techsolution-form-studio') },
                    picker,
                    el(ToggleControl, {
                        label: __('Show form title', 'techsolution-form-studio'),
                        checked: attributes.showTitle,
                        onChange: function (value) { props.setAttributes({ showTitle: value }); }
                    })
                )
            );

            if (!attributes.formId) {
                return el('div', blockProps, inspector,
                    el(Placeholder, {
                        icon: 'feedback',
                        label: __('Form Studio Form', 'techsolution-form-studio'),
                        instructions: forms.length ? __('Choose the form to display.', 'techsolution-form-studio') : __('Create a form under Form Studio → Add New first.', 'techsolution-form-studio')
                    }, forms.length ? picker : null)
                );
            }

            return el('div', blockProps, inspector,
                el(ServerSideRender, { block: 'techsolution-form-studio/form', attributes: attributes })
            );
        },
        save: function () {
            return null;
        }
    });
})(window.wp);
