/**
 * jQuery mock for testing
 */

const jQueryMock = function(selector) {
    const element = {
        val: jest.fn().mockReturnThis(),
        text: jest.fn().mockReturnThis(),
        html: jest.fn().mockReturnThis(),
        attr: jest.fn().mockReturnThis(),
        data: jest.fn().mockReturnThis(),
        addClass: jest.fn().mockReturnThis(),
        removeClass: jest.fn().mockReturnThis(),
        show: jest.fn().mockReturnThis(),
        hide: jest.fn().mockReturnThis(),
        fadeIn: jest.fn().mockReturnThis(),
        fadeOut: jest.fn().mockReturnThis(),
        prop: jest.fn().mockReturnThis(),
        find: jest.fn().mockReturnThis(),
        click: jest.fn().mockReturnThis(),
        on: jest.fn().mockReturnThis(),
        off: jest.fn().mockReturnThis(),
        trigger: jest.fn().mockReturnThis(),
        is: jest.fn(() => false),
        length: 1
    };

    // Mock common jQuery methods
    element.ajax = jest.fn();
    element.post = jest.fn();
    element.get = jest.fn();
    
    return element;
};

// Mock jQuery static methods
jQueryMock.ajax = jest.fn();
jQueryMock.post = jest.fn();
jQueryMock.get = jest.fn();
jQueryMock.when = jest.fn();
jQueryMock.extend = jest.fn();
jQueryMock.each = jest.fn();

// Mock document ready
jQueryMock.fn = {
    extend: jest.fn()
};

module.exports = jQueryMock;