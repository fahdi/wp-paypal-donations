(function() {
    'use strict';
    
    const { registerBlockType } = wp.blocks;
    const { __ } = wp.i18n;
    const { 
        useBlockProps, 
        InspectorControls, 
        RichText 
    } = wp.blockEditor;
    const { 
        PanelBody, 
        TextControl, 
        ToggleControl 
    } = wp.components;
    const { createElement: el, Fragment } = wp.element;
    
    registerBlockType('wp-paypal-donations/donation-form', {
        title: __('Donation Form', 'wp-paypal-donations'),
        icon: 'heart',
        category: 'widgets',
        description: __('A customizable PayPal donation form with quick amount buttons and recurring donation options.', 'wp-paypal-donations'),
        keywords: [__('donation', 'wp-paypal-donations'), __('paypal', 'wp-paypal-donations'), __('fundraising', 'wp-paypal-donations')],
        
        attributes: {
            amount: {
                type: 'string',
                default: '25'
            },
            title: {
                type: 'string',
                default: __('Make a Donation', 'wp-paypal-donations')
            },
            showQuickAmounts: {
                type: 'boolean',
                default: true
            },
            buttonText: {
                type: 'string',
                default: __('Donate with PayPal', 'wp-paypal-donations')
            }
        },
        
        supports: {
            html: false,
            align: ['left', 'center', 'right', 'wide', 'full']
        },
        
        edit: function(props) {
            const { attributes, setAttributes } = props;
            const {
                amount,
                title,
                showQuickAmounts,
                buttonText
            } = attributes;
            
            const blockProps = useBlockProps({
                className: 'wpd-donation-form-block-editor'
            });
            
            return el(Fragment, {},
                el(InspectorControls, {},
                    el(PanelBody, { 
                        title: __('Donation Settings', 'wp-paypal-donations') 
                    },
                        el(TextControl, {
                            label: __('Default Amount', 'wp-paypal-donations'),
                            value: amount,
                            onChange: function(value) { setAttributes({ amount: value }); },
                            type: 'number',
                            min: '0.01',
                            step: '0.01'
                        }),
                        el(TextControl, {
                            label: __('Button Text', 'wp-paypal-donations'),
                            value: buttonText,
                            onChange: function(value) { setAttributes({ buttonText: value }); }
                        }),
                        el(ToggleControl, {
                            label: __('Show Quick Amount Buttons', 'wp-paypal-donations'),
                            checked: showQuickAmounts,
                            onChange: function(value) { setAttributes({ showQuickAmounts: value }); }
                        })
                    )
                ),
                
                el('div', blockProps,
                    el('div', { className: 'wpd-block-preview' },
                        el(RichText, {
                            tagName: 'h3',
                            className: 'wpd-form-title',
                            value: title,
                            onChange: function(value) { setAttributes({ title: value }); },
                            placeholder: __('Enter donation form title...', 'wp-paypal-donations')
                        }),
                        
                        el('div', { className: 'wpd-form-preview' },
                            el('div', { className: 'wpd-form-row' },
                                el('label', {}, 
                                    __('Full Name', 'wp-paypal-donations'),
                                    el('span', { className: 'required' }, ' *')
                                ),
                                el('input', { 
                                    type: 'text', 
                                    placeholder: __('Enter your full name', 'wp-paypal-donations'),
                                    disabled: true 
                                })
                            ),
                            
                            el('div', { className: 'wpd-form-row' },
                                el('label', {}, 
                                    __('Email Address', 'wp-paypal-donations'),
                                    el('span', { className: 'required' }, ' *')
                                ),
                                el('input', { 
                                    type: 'email', 
                                    placeholder: __('Enter your email', 'wp-paypal-donations'),
                                    disabled: true 
                                })
                            ),
                            
                            el('div', { className: 'wpd-form-row' },
                                el('label', {}, 
                                    __('Donation Amount', 'wp-paypal-donations'),
                                    el('span', { className: 'required' }, ' *')
                                ),
                                el('div', { className: 'wpd-amount-container' },
                                    el('span', { className: 'wpd-currency-symbol' }, '$'),
                                    el('input', { 
                                        type: 'number', 
                                        value: amount,
                                        onChange: function(e) { setAttributes({ amount: e.target.value }); },
                                        min: '0.01',
                                        step: '0.01'
                                    })
                                )
                            ),
                            
                            showQuickAmounts && el('div', { className: 'wpd-quick-amounts' },
                                el('button', { type: 'button', className: 'wpd-quick-amount', disabled: true }, '$10'),
                                el('button', { type: 'button', className: 'wpd-quick-amount', disabled: true }, '$25'),
                                el('button', { type: 'button', className: 'wpd-quick-amount', disabled: true }, '$50'),
                                el('button', { type: 'button', className: 'wpd-quick-amount', disabled: true }, '$100')
                            ),
                            
                            el('div', { className: 'wpd-form-row' },
                                el('button', { 
                                    type: 'button', 
                                    className: 'wpd-donate-btn',
                                    disabled: true 
                                }, buttonText)
                            ),
                            
                            el('div', { className: 'wpd-security-info' },
                                el('small', {}, 
                                    '🔒 ' + __('Secure payment processed by PayPal', 'wp-paypal-donations')
                                )
                            )
                        )
                    )
                )
            );
        },
        
        save: function() {
            return null; // Dynamic block, rendered by PHP
        }
    });
})();