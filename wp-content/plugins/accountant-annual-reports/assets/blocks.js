(function (wp) {
  'use strict';
  const el = wp.element.createElement;
  const __ = wp.i18n.__;
  const blocks = {
    reports: __('Annual reports', 'accountant-annual-reports'),
    'report-details': __('Annual report details', 'accountant-annual-reports'),
    contact: __('Office contact details', 'accountant-annual-reports'),
    'contact-summary': __('Office email and phone', 'accountant-annual-reports'),
    copyright: __('Current year and site name', 'accountant-annual-reports'),
    'legal-links': __('Published policy links', 'accountant-annual-reports')
  };
  Object.entries(blocks).forEach(function ([name, title]) {
    wp.blocks.registerBlockType('accountant/' + name, {
      apiVersion: 3, title: title, icon: name === 'reports' ? 'media-document' : 'admin-site-alt3', category: 'widgets',
      attributes: name === 'reports' ? {limit: {type: 'number', default: 3}, archive: {type: 'boolean', default: false}, align: {type: 'string', default: 'wide'}} : {},
      supports: {html: false, align: ['wide', 'full']},
      edit: function (props) {
        const preview = el(wp.serverSideRender, {block: 'accountant/' + name, attributes: props.attributes});
        const helper = (name === 'contact' || name === 'contact-summary') ? el('p', {}, __('Edit these shared details in Tools → Accountant Setup.', 'accountant-annual-reports')) : null;
        const controls = name === 'reports' && !props.attributes.archive ? el(wp.blockEditor.InspectorControls, {}, el(wp.components.PanelBody, {title: title}, el(wp.components.RangeControl, {label: __('Number of reports', 'accountant-annual-reports'), value: props.attributes.limit, min: 1, max: 12, onChange: function (limit) { props.setAttributes({limit: limit}); }}))) : null;
        return el('div', wp.blockEditor.useBlockProps(), controls, helper, preview);
      },
      save: function () { return null; }
    });
  });
}(window.wp));
