import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { 
    useBlockProps, 
    InspectorControls, 
    RichText 
} from '@wordpress/block-editor';
import { 
    PanelBody, 
    TextControl, 
    ToggleControl 
} from '@wordpress/components';
import './editor.scss';

registerBlockType('wp-paypal-donations/donation-form', {
    edit: ({ attributes, setAttributes }) => {
        const blockProps = useBlockProps({
            className: 'wpd-donation-form-block-editor'
        });
        
        const {
            amount,
            title,
            showQuickAmounts,
            buttonText
        } = attributes;
        
        return (
            <>
                <InspectorControls>
                    <PanelBody title={__('Donation Settings', 'wp-paypal-donations')}>
                        <TextControl
                            label={__('Default Amount', 'wp-paypal-donations')}
                            value={amount}
                            onChange={(value) => setAttributes({ amount: value })}
                            type="number"
                            min="0.01"
                            step="0.01"
                        />
                        <TextControl
                            label={__('Button Text', 'wp-paypal-donations')}
                            value={buttonText}
                            onChange={(value) => setAttributes({ buttonText: value })}
                        />
                        <ToggleControl
                            label={__('Show Quick Amount Buttons', 'wp-paypal-donations')}
                            checked={showQuickAmounts}
                            onChange={(value) => setAttributes({ showQuickAmounts: value })}
                        />
                    </PanelBody>
                </InspectorControls>
                
                <div {...blockProps}>
                    <div className="wpd-block-preview">
                        <RichText
                            tagName="h3"
                            className="wpd-form-title"
                            value={title}
                            onChange={(value) => setAttributes({ title: value })}
                            placeholder={__('Enter donation form title...', 'wp-paypal-donations')}
                        />
                        
                        <div className="wpd-form-preview">
                            <div className="wpd-form-row">
                                <label>{__('Full Name', 'wp-paypal-donations')} <span className="required">*</span></label>
                                <input type="text" placeholder={__('Enter your full name', 'wp-paypal-donations')} disabled />
                            </div>
                            
                            <div className="wpd-form-row">
                                <label>{__('Email Address', 'wp-paypal-donations')} <span className="required">*</span></label>
                                <input type="email" placeholder={__('Enter your email', 'wp-paypal-donations')} disabled />
                            </div>
                            
                            <div className="wpd-form-row">
                                <label>{__('Donation Amount', 'wp-paypal-donations')} <span className="required">*</span></label>
                                <div className="wpd-amount-container">
                                    <span className="wpd-currency-symbol">$</span>
                                    <input type="number" value={amount} onChange={(e) => setAttributes({ amount: e.target.value })} min="0.01" step="0.01" />
                                </div>
                            </div>
                            
                            {showQuickAmounts && (
                                <div className="wpd-quick-amounts">
                                    <button type="button" className="wpd-quick-amount" disabled>$10</button>
                                    <button type="button" className="wpd-quick-amount" disabled>$25</button>
                                    <button type="button" className="wpd-quick-amount" disabled>$50</button>
                                    <button type="button" className="wpd-quick-amount" disabled>$100</button>
                                </div>
                            )}
                            
                            <div className="wpd-form-row">
                                <button type="button" className="wpd-donate-btn" disabled>
                                    {buttonText}
                                </button>
                            </div>
                            
                            <div className="wpd-security-info">
                                <small>
                                    🔒 {__('Secure payment processed by PayPal', 'wp-paypal-donations')}
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </>
        );
    },
    
    save: () => {
        return null; // Dynamic block, rendered by PHP
    }
});