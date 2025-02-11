// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

define([''], function() {
    return class MmoGame {
        // Define default properties with appropriate types
        state; // State of the game
        body;
        minFontSize;
        maxFontSize;
        fontSize;
        iconSize;
        padding;
        cIcons;

        // Colors
        colorBackground;

        // Timer variables
        timestart = 0;
        timeclose = 0;

        /**
         * Initialize game properties and compute initial sizes.
         *
         * @module mmogame
         * @copyright 2024 Vasilis Daloukas
         * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
         */
        constructor() {
            // Initialize default properties.
            this.kindSound = 0;
            this.state = 0;
            this.minFontSize = 0;
            this.maxFontSize = 0;
            this.fontSize = 0;
            this.iconSize = 0;
            this.padding = 0;
            this.body = document.getElementsByTagName("body")[0];
            this.setColorsString();

            // Compute and set font size properties.
            const size = parseFloat(getComputedStyle(document.documentElement).fontSize);
            this.minFontSize = size;
            this.maxFontSize = 2 * size;
            this.fontSize = size;
        }

        // UI element creation methods
        /**
         * Creates a DOM element with specified attributes and styles.
         * @param {string} tag - The HTML tag to create (e.g., 'div', 'img').
         * @param {Object} options - Configuration for the element.
         * @param {HTMLElement} options.parent - Parent element where the new element will be appended.
         * @param {string} [options.classnames] - Space-separated string of CSS class names.
         * @param {Object} [options.styles] - Inline styles for the element.
         * @param {Object} [options.attributes] - Attributes for the element (e.g., src, alt, role).
         * @returns {HTMLElement} - The created DOM element.
         */
        createDOMElement(tag, {parent, classnames = '', styles = {}, attributes = {}} = {}) {
            const element = document.createElement(tag);

            // Apply classes
            if (classnames) {
                element.classList.add(...classnames.split(/\s+/));
            }

            // Apply styles
            Object.assign(element.style, styles);

            // Apply attributes
            Object.entries(attributes).forEach(([key, value]) => {
                if (value !== undefined && value !== null) {
                    element.setAttribute(key, value);
                }
            });

            // Append to parent
            if (parent) {
                parent.appendChild(element);
            }

            return element;
        }

        /**
         * Creates a <div> element.
         *
         * @param {HTMLElement} parent - The parent element.
         * @param {string} classnames - Horizontal position in pixels.
         * @param {number} left - Horizontal position in pixels.
         * @param {number} top - Vertical position in pixels.
         * @param {number} width - Width of the <div>.
         * @param {number} height - Height of the <div>.
         * @returns {HTMLElement} - The created <div> element.
         */
        createDiv(parent, classnames, left, top, width, height) {
            return this.createDOMElement('div', {
                parent,
                classnames,
                styles: {
                    position: 'absolute',
                    left: `${left}px`,
                    top: `${top}px`,
                    width: `${width}px`,
                    height: `${height}px`,
                },
            });
        }

        /**
         * Creates an <img> element.
         *
         * @param {HTMLElement} parent - The parent element.
         * @param {string} classnames - List of classes separated by space.
         * @param {number} left - Horizontal position in pixels.
         * @param {number} top - Vertical position in pixels.
         * @param {number} width - Width of the <img> element.
         * @param {number} height - Height of the <img> element.
         * @param {string} filename - The source file for the image.
         * @returns {HTMLElement} - The created <img> element.
         */
        createImage(parent, classnames, left, top, width, height, filename) {
            const styles = {
                position: 'absolute',
                left: `${left}px`,
                top: `${top}px`,
            };

            // Only add width to styles if it's not 0
            if (width !== 0) {
                styles.width = `${width}px`;
            }

            // Only add height to styles if it's not 0
            if (height !== 0) {
                styles.height = `${height}px`;
            }

            const attributes = {draggable: false};
            if (filename !== '') {
                attributes.src = filename;
            }

            return this.createDOMElement('img', {
                parent,
                classnames,
                styles,
                attributes,
            });
        }

        // For check
        createButton(parent, classnames, left, top, width, height, src, alt, role = 'button') {
            return this.createDOMElement('img', {
                parent,
                classnames,
                styles: {
                    position: 'absolute',
                    left: `${left}px`,
                    top: `${top}px`,
                    width: `${width}px`,
                    height: `${height}px`,
                },
                attributes: {src, alt, role},
            });
        }

        // Game logic and utility methods

        /**
         * Compute sizes for icons and padding based on the screen dimensions.
         */
        computeSizes() {
            const cIcons = Math.max(this.cIcons || 5, 5);
            const maxIconWidth = window.innerWidth / cIcons;
            const maxIconHeight = window.innerHeight / 5;
            this.iconSize = Math.min(maxIconWidth, maxIconHeight);
            const adjustment = this.iconSize / 10 / cIcons;
            this.iconSize = Math.round(this.iconSize - adjustment);
            this.padding = Math.round(this.iconSize / 10);
            this.iconSize -= this.padding;
        }

        /**
         * Creates a centered image button with automatic scaling.
         * @param {HTMLElement} parent - The parent element where the button will be appended.
         * @param {number} left - The left position of the container in pixels.
         * @param {number} top - The top position of the container in pixels.
         * @param {number} width - The width of the container in pixels.
         * @param {number} height - The height of the container in pixels.
         * @param {string} classname - Additional CSS classes to apply to the button.
         * @param {string} filename - The source URL of the image.
         * @returns {HTMLElement} - The created image button element.
         */
        createCenterImageButton(parent, left, top, width, height, classname, filename) {
            const button = this.createDOMElement('img', {
                parent,
                classnames: `mmogame_imgbutton ${classname}`,
                styles: {
                    position: 'absolute',
                    draggable: false,
                },
            });

            const img = new Image();
            img.onload = function() {
                if (this.width > 0 && this.height > 0) {
                    const mul = Math.min(width / this.width, height / this.height);
                    const w = Math.round(this.width * mul);
                    const h = Math.round(this.height * mul);

                    Object.assign(button.style, {
                        width: `${w}px`,
                        height: `${h}px`,
                        left: `${left + width / 2 - w / 2}px`,
                        top: `${top + height / 2 - h / 2}px`,
                    });

                    button.src = filename;
                }
            };
            img.src = filename;

            return button;
        }

        hasHelp() {
            return false;
        }

        /**
         * Clear all children of the <body> element.
         */
        clearBodyChildren() {
            this.removeDivMessage();
            let child;
            while ((child = this.body.firstChild)) {
                this.body.removeChild(child);
            }
        }

        /**
         * Opens the game by resetting the body and computing sizes.
         */
        openGame() {
            this.clearBodyChildren();
            this.computeSizes();
        }

        autoResizeText(item, width, height, wrap, minFontSize, maxFontSize) {
            const text = item.innerHTML.toString();

            if (text.length === 0) {
                return;
            }

            const tempDiv = document.createElement("div");
            tempDiv.style.visibility = "hidden";
            tempDiv.style.position = "absolute";
            tempDiv.style.whiteSpace = wrap ? "normal" : "nowrap";

            this.body.appendChild(tempDiv);

            let low = Math.max(1, minFontSize);
            let up = maxFontSize || Math.min(width, height);

            let fitSize = low;
            let fitHeight = 0;
            let newHeight = 0;
            let newWidth = 0;

            let step = 1;
            let fontSize;
            for (; step <= 10; step++) {
                fontSize = (low + up) / 2;
                tempDiv.style.fontSize = `${fontSize}px`;
                tempDiv.style.width = `${width}px`;
                tempDiv.style.height = `0`;
                tempDiv.innerHTML = text;

                newHeight = tempDiv.scrollHeight;
                newWidth = tempDiv.scrollWidth - 1;
                if (newWidth > width || newHeight > height) {
                    up = fontSize;
                } else {
                    low = fontSize;
                    if (Math.abs(fitHeight - newHeight) <= 2) {
                        break;
                    }
                    fitSize = fontSize;
                    fitHeight = newHeight;
                }
            }
            item.style.fontSize = `${fitSize}px`;
            this.body.removeChild(tempDiv);
        }

        /**
         * Returns the hex color string for a given color code.
         *
         * @param {Number} colorCode - The color code.
         * @returns {string} Hexadecimal representation of the color.
         */
        getColorHex(colorCode) {
            return `#${colorCode.toString(16).padStart(6, '0').toUpperCase()}`;
        }

        /**
         * Calculates contrast value for a given color.
         *
         * @param {Number} colorCode - The color code.
         * @returns {Number} Contrast value.
         */
        getContrast(colorCode) {
            // eslint-disable-next-line no-bitwise
            const r = (colorCode >> 16) & 0xff;
            // eslint-disable-next-line no-bitwise
            const g = (colorCode >> 8) & 0xff;
            // eslint-disable-next-line no-bitwise
            const b = colorCode & 0xff;
            return (r * 299 + g * 587 + b * 114) / 1000;
        }

        /**
         * Returns a contrasting color (black or white) based on brightness.
         * @param {Number} colorCode - The color code.
         * @returns {string} "#000000" or "#FFFFFF".
         */
        getContrastingColor(colorCode) {
            // eslint-disable-next-line no-bitwise
            const r = (colorCode >> 16) & 0xff;
            // eslint-disable-next-line no-bitwise
            const g = (colorCode >> 8) & 0xff;
            // eslint-disable-next-line no-bitwise
            const b = colorCode & 0xff;
            const brightness = (r * 299 + g * 587 + b * 114) / 1000;
            return brightness >= 128 ? "#000000" : "#FFFFFF";
        }

        /**
         * Repairs <p> tags in a string by cleaning up unnecessary tags.
         * @param {string} text - The input string with potential <p> tags.
         * @returns {string} The cleaned-up string.
         */
        repairP(text) {
            if (!text) {
                return '';
            }
            return text.replace(/<p[^>]*>/g, '').replace(/<\/p>/g, '<br>').trim();
        }

        /**
         * Finds the best value based on a condition.
         * @param {number} low - The lower bound.
         * @param {number} high - The upper bound.
         * @param {function} condition - A condition to evaluate.
         */
        findbest(low, high, condition) {
            while (high - low > 1) {
                const mid = Math.floor((low + high) / 2);
                if (condition(mid) < 0) {
                    low = mid;
                } else {
                    high = mid;
                }
            }

            return low;
        }

        removeDivMessage() {
            if (this.divMessage !== undefined) {
                this.body.removeChild(this.divMessage);
                this.divMessage = undefined;
            }
            if (this.divMessageHelp !== undefined) {
                this.body.removeChild(this.divMessageHelp);
                this.divMessageHelp = undefined;
            }
            if (this.divMessageBackground !== undefined) {
                this.divMessageBackground.remove();
                this.divMessageBackground = undefined;
            }
        }

        disableButtons(buttons, disabled) {
            for (let i = 0; i < buttons.length; i++) {
                let btn = buttons[i];
                if (btn !== undefined) {
                    if (disabled) {
                        btn.classList.add("mmogame_imgbutton_disabled");
                    } else {
                        btn.classList.remove("mmogame_imgbutton_disabled");
                    }
                }
            }
        }

        repairNickname(nickname) {
            if (nickname === undefined) {
                return '';
            }

            let s = nickname;
            if (s !== '') {
                while (s.indexOf('_') !== -1) {
                    s = s.replace('_', ' ');
                }
            }

            return s;
        }

        showColorPalette(canvas, colors) {
            let ctx = canvas.getContext("2d");
            let width = canvas.width;
            let height = canvas.height;
            let strip = width / 5;

            // Sort colors based on their contrast value using an arrow function
            colors.sort((a, b) => this.getContrast(a) - this.getContrast(b));

            for (let i = 0; i < 5; i++) {
                ctx.fillStyle = this.getColorHex(colors[i]);
                ctx.fillRect(i * strip, 0, (i + 1) * strip, height);
            }

            ctx.strokeStyle = "#FFFFFF";
            ctx.lineWidth = 1;
            ctx.strokeRect(0, 0, width, height);
        }

        setColorsString(s) {
            let colors = [0x9B7ED9, 0x79F2F2, 0x67BF5A, 0xD0F252, 0xBF5B21];
            if (s !== undefined && s.length >= 0) {
                let b = s.split(",");
                if (b.length === 5) {
                    colors = [];
                    for (let i = 0; i < 5; i++) {
                        colors[i] = parseInt(b[i]);
                    }
                }
            }

            this.setColors(this.sortColors(colors));
        }

        sortColors(colors) {
            return colors.sort((a, b) => this.getContrast(a) - this.getContrast(b));
        }

        computeDifClock(time, timeStart, timeClose) {
            if (time !== undefined) {
                this.difClock = ((new Date()).getTime() - time) / 1000;
            }

            this.computeTimeStartClose(timeStart, timeClose);
        }

        computeTimeStartClose(timeStart, timeClose) {
            if (timeStart !== undefined) {
                this.timestart = parseInt(timeStart) !== 0 ? parseInt(timeStart) + this.difClock : 0;
                this.timeclose = parseInt(timeClose) !== 0 ? parseInt(timeClose) + this.difClock : 0;
            } else {
                this.timestart = 0;
                this.timeclose = 0;
            }
        }

        drawRadio(canvas, color1, color2) {
            let ctx = canvas.getContext("2d");
            let size = canvas.width;
            ctx.clearRect(0, 0, size, canvas.height);

            ctx.beginPath();
            ctx.arc(size / 2, size / 2, size / 2, 0, 2 * Math.PI, false);
            ctx.fillStyle = this.getColorHex(color1);
            ctx.fill();

            let checked = canvas.classList.contains("checked");
            if (checked) {
                ctx.beginPath();
                ctx.arc(size / 2, size / 2, size / 4, 0, 2 * Math.PI, false);
                ctx.fillStyle = this.getColorHex(color2);
                ctx.fill();
            }
        }

        createRadiobox(parent, size, color1, color2, checked, disabled) {
            let canvas = document.createElement('canvas');
            canvas.style.position = "absolute";
            canvas.width = size;
            canvas.height = size;
            parent.appendChild(canvas);
            if (checked) {
                canvas.classList.add("checked");
            }
            if (disabled) {
                canvas.classList.add("disabled");
            }

            this.drawRadio(canvas, disabled ? color1 : 0xFFFFFF, color2);

            return canvas;
        }

        createImageButton(parent, classnames, left, top, width, height, filename) {
            const imgButton = this.createImage(parent, classnames, left, top, width, height, filename);
            imgButton.style.cursor = 'pointer';
            return imgButton;
        }

        createDivColor(parent, classnames, left, top, width, height, color) {
            const colorDiv = this.createDiv(parent, classnames, left, top, width, height);
            colorDiv.style.backgroundColor = color;
            colorDiv.style.border = '1px solid #000';
            return colorDiv;
        }

        /**
         * Retrieves localized strings.
         * @param {string} name - The name of the string.
         * @returns {string} The localized string.
         */
        getStringM(name) {
            return M.util.get_string(name, 'mmogame');
        }

        /**
         * Retrieves user options from IndexedDB.
         * @param {string}name
         * @returns {Promise<Object>} A promise that resolves with the options.
         */
        async getOption(name) {
            if (typeof name !== "string" || !name.trim()) {
                throw new Error("name must be a non-empty string");
            }

            return new Promise((resolve, reject) => {
                const request = indexedDB.open('mmoGameDB', 1);

                request.onupgradeneeded = function(event) {
                    const db = event.target.result;
                    // Create the "options" object store if it doesn't exist
                    if (!db.objectStoreNames.contains('options')) {
                        db.createObjectStore('options', {keyPath: 'name'});
                    }
                };

                request.onsuccess = function(event) {
                    const db = event.target.result;
                    const transaction = db.transaction(['options'], 'readonly');
                    const store = transaction.objectStore('options');

                    const getRequest = store.get(name);
                    getRequest.onsuccess = function(event) {
                        resolve(event.target.result || null); // Return the full object or null if not found
                    };
                    getRequest.onerror = function() {
                        reject(new Error(`Failed to retrieve option: ${name}`));
                    };
                };

                request.onerror = function() {
                    reject(new Error('Failed to open database'));
                };
            });
        }

        /**
         * Saves user options to IndexedDB.
         * @param {string}name
         * @param {object}data
         * @returns {Promise<void>} A promise that resolves when the save is complete.
         */
        async setOption(name, data) {
            if (typeof name !== "string" || !name.trim()) {
                throw new Error("name must be a non-empty string");
            }
            if (typeof data !== "object" || data === null) {
                throw new Error("data must be a non-null object");
            }

            return new Promise((resolve, reject) => {
                const request = indexedDB.open('mmoGameDB', 1);

                request.onupgradeneeded = function(event) {
                    const db = event.target.result;
                    // Create the "options" object store if it doesn't exist
                    if (!db.objectStoreNames.contains('options')) {
                        db.createObjectStore('options', {keyPath: 'name'});
                    }
                };

                request.onsuccess = function(event) {
                    const db = event.target.result;
                    const transaction = db.transaction(['options'], 'readwrite');
                    const store = transaction.objectStore('options');

                    // Ensure the object contains the correct key
                    const record = {name, ...data};

                    const putRequest = store.put(record);
                    putRequest.onsuccess = function() {
                        resolve(true);
                    };
                    putRequest.onerror = function() {
                        reject(new Error(`Failed to save option: ${name}`));
                    };
                };

                request.onerror = function() {
                    reject(new Error('Failed to open database'));
                };
            });
        }

        debounce(func, delay) {
            let timer;
            return function(...args) {
                clearTimeout(timer);
                timer = setTimeout(() => func.apply(this, args), delay);
            };
        }

        /**
         * Displays an error message on the screen.
         * @param {string} name - The name of the error context.
         * @param {Error} [error] - The error object to display.
         */
        showError(name, error) {
            return name + error;
        }

        setColors(colors) {
            this.colorBackground = colors[0];
            this.body.style.backgroundColor = this.getColorHex(this.colorBackground);
        }
    };
});