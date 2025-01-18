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
        avatarTop;
        colors = {};
        iconSize;
        padding;
        cIcons;

        // UI element placeholders
        area;

        // Colors
        colorScore;
        colorScore2;
        colorDefinition;
        colorsBackground;

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
            this.avatarTop = 0;
            this.iconSize = 0;
            this.padding = 0;
            this.body = document.getElementsByTagName("body")[0];

            // Compute and set font size properties.
            let size = parseFloat(window.getComputedStyle(document.documentElement).getPropertyValue('font-size'));
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

        /**
         * Generic method to create styled text or input fields.
         * @param {HTMLElement} parent - Parent element where the new element will be appended.
         * @param {string} type - Type of the element ('label' or 'input').
         * @param {string} classnames - CSS class names.
         * @param {Object} styles - Inline styles.
         * @param {string} text - Inner text or placeholder.
         * @returns {HTMLElement} - The created element.
         */
        createTextElement(parent, type, classnames, styles, text = '') {
            const attributes = type === 'input' ? {type: 'text', placeholder: text} : {};
            const element = this.createDOMElement(type, {parent, classnames, styles, attributes});
            if (type === 'label') {
                element.innerText = text;
            }
            return element;
        }

        createLabel(parent, classnames, left, top, width, fontSize, text) {
            return this.createTextElement(parent, 'label', classnames, {
                position: 'absolute',
                left: `${left}px`,
                top: `${top}px`,
                width: `${width}px`,
                fontSize: `${fontSize}px`,
                textAlign: 'left',
            }, text);
        }

        createInput(parent, classnames, left, top, width, fontSize, placeholder) {
            return this.createTextElement(parent, 'input', classnames, {
                position: 'absolute',
                left: `${left}px`,
                top: `${top}px`,
                width: `${width}px`,
                fontSize: `${fontSize}px`,
            }, placeholder);
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

        createDivButton(classnames, left, top) {
            return this.createButton(
                this.body,
                classnames,
                left,
                top,
                this.iconSize,
                this.iconSize,
                '',
                '',
                'button');
        }


        // Game Logic

        // Utility Functions

        // Other

        hasHelp() {
            return false;
        }

        /**
         * Clear all children of the <body> element.
         */
        clearBodyChildren() {
            this.removeDivMessage();

            while (this.body.firstChild) {
                this.body.removeChild(this.body.firstChild);
            }
            this.area = undefined;
        }

        /**
         * Opens the game by resetting the body and computing sizes.
         */
        openGame() {
            this.clearBodyChildren();
            this.computeSizes();
        }

        /**
         * Updates an image button's properties.
         * @param {HTMLImageElement} button - The button element to update.
         * @param {string} src - The new source for the image.
         */
        updateImageButton(button, src) {
            button.src = src;
        }

        autoResizeText(item, width, height, wrap, minFontSize, maxFontSize, minRatio) {
            const text = item.innerHTML.toString();

            if (text.length === 0) {
                return false;
            }

            let low = Math.max(1, minFontSize);
            width = Math.round(width);
            height = Math.round(height);
            let up = maxFontSize === 0 || maxFontSize === undefined ? Math.min(width, height) : maxFontSize;

            let fitSize = low;
            let fitHeight = 0;
            let newHeight = 0;
            let newWidth = 0;

            let i = 1;
            for (;i <= 10; i++) {
                let el = document.createElement("div");
                el.style.left = 0;
                el.style.top = 0;
                el.style.width = width + "px";
                el.style.height = 0;
                el.visibility = "visible";
                if (!wrap) {
                    el.style.whiteSpace = "nowrap";
                }
                el.innerHTML = text;
                this.body.appendChild(el);

                let fontSize = (low + up) / 2;

                el.style.fontSize = fontSize + "px";
                newHeight = el.scrollHeight;
                newWidth = el.scrollWidth - 1;

                this.body.removeChild(el);

                if (newWidth > width || newHeight > height) {
                    up = fontSize;
                } else {
                    low = fontSize;
                    if (Math.abs(fitHeight - newHeight) <= 2) {
                        break;
                    }
                    fitHeight = newHeight;
                    fitSize = fontSize;
                }
            }
            item.style.fontSize = fitSize + "px";

            if (newWidth > width || newHeight > height) {
                this.autoResizeTextBr(item);
                newWidth = item.scrollWidth;
                newHeight = item.scrollHeight;
                this.autoResizeTextImage(item, newWidth > width ? newWidth - width : 0,
                    newHeight > height ? newHeight - height : 0, minRatio);
            } else {
                return [item.scrollWidth - 1, item.scrollHeight];
            }

            let el = document.createElement("div");
            el.style.width = width + "px";
            el.style.height = 0;
            el.visibility = "hidden";
            if (!wrap) {
                el.style.whiteSpace = "nowrap";
            }
            el.innerHTML = text;
            this.body.appendChild(el);
            el.style.fontSize = item.style.fontSize;
            let size = [el.scrollWidth - 1, el.scrollHeight];
            this.body.removeChild(el);

            return size;
        }

        autoResizeTextBr(item) {
            let s = item.innerHTML;
            let change = false;
            while (s.startsWith('<br>')) {
                s = s.substring(4);
                change = true;
            }
            let pos1 = s.indexOf('<br>');
            for (; ;) {
                let pos = s.indexOf('<br>', pos1 + 4);
                if (pos < 0) {
                    break;
                }
                let s2 = s.substring(pos1 + 4, pos);
                if (!s2.trim()) {
                    s = s.substring(0, pos1 + 4) + s.substring(pos + 4);
                    change = true;
                    pos = pos1;
                }
                pos1 = pos;
            }

            if (change) {
                item.innerHTML = s;
            }
        }

        autoResizeTextImage(item, subwidth, subheight, minRatio) {
            if (subwidth === 0 && subheight === 0) {
                return;
            }
            let s = item.innerHTML;

            for (let pos = 0; ;) {
                let pos2 = s.indexOf("<img ", pos);
                if (pos2 < 0) {
                    break;
                }
                let pos3 = s.indexOf(">", pos2);
                if (pos3 < 0) {
                    break;
                }
                let s2 = s.substring(pos2, pos3) + " ";

                let width = 0;
                let height = 0;
                let posw = s2.indexOf("width=");
                if (posw >= 0) {
                    let posw2 = s2.indexOf(" ", posw);
                    if (posw2 >= 0) {
                        let num = s2.slice(posw + 6, posw2).replace(/"/g, "");
                        width = parseInt(num);
                        s2 = s2.slice(0, posw) + s2.slice(posw2);
                    }
                }

                posw = s2.indexOf("height=");
                if (posw >= 0) {
                    let posw2 = s2.indexOf(" ", posw);
                    if (posw2 >= 0) {
                        let num = s2.slice(posw + 7, posw2).replace(/"/g, "");
                        height = parseInt(num);
                        s2 = s2.slice(0, posw) + s2.slice(posw2);
                    }
                }
                if (width > 0 && height > 0) {
                    let newWidth = width - subwidth > 0 ? width - subwidth : width / 2;
                    let newHeight = height - subheight > 0 ? height - subheight : height / 2;
                    let ratio = Math.max(minRatio, Math.min(newWidth / width, newHeight / height));
                    s2 = s2 + " width=\"" + Math.round(ratio * width) + "\" height=\"" + Math.round(height * ratio) + "\" ";
                }
                s = s.slice(0, pos2) + s2 + s.slice(pos3);
                pos = pos3;
            }
            item.innerHTML = s;
        }

        pad(num, size) {
            let s = num + "";
            while (s.length < size) {
                s = "0" + s;
            }
            return s;
        }

        uuid4() {
            const hexDigits = '0123456789abcdef';
            const uuid = [...Array(36)].map(() => hexDigits[Math.floor(Math.random() * 16)]);
            uuid[8] = uuid[13] = uuid[18] = uuid[23] = '-';
            uuid[14] = '4';
            // eslint-disable-next-line no-bitwise
            uuid[19] = hexDigits[(parseInt(uuid[19], 16) & 0x3) | 0x8];

            this.user = uuid.join('');

            let options = {userGUID: this.user};
            let instance = this;
            this.setOptions(options)
                .then(function() {
                    return true;
                })
                .catch(error => {
                    console.log(error);
                    instance.showError(error.message);
                    return false;
                });
        }

        getCopyrightHeight() {
            return Math.round(this.iconSize / 3);
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

        getColorGray(x) {
            let r = Math.floor(x / 0x1000000) % 256, // Red.
                g = Math.floor(x / 0x10000) % 256, // Green.
                b = Math.floor(x / 0x100) % 256, // Blue.
                yiq = (r * 299) + g * 587 + b * 114,
                m = 255 * 299 + 255 * 587 + 255 * 114,
                gray = Math.round(yiq * 255 / m);

            return (gray * 0x10000) + (gray * 0x100) + gray;
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
         * Repairs colors by sorting and assigning contrasting background colors.
         * @param {Array} colors - Array of color codes.
         */
        repairColors(colors) {
            this.colors = colors.sort((a, b) => this.getContrast(a) - this.getContrast(b));
            this.colorBackground = this.colors[0];
            this.body.style.backgroundColor = this.getColorHex(this.colorBackground);
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
         * Creates a help button.
         * @param {number} left - Left position in pixels.
         * @param {number} top - Top position in pixels.
         */
        createButtonHelp(left, top) {
            const helpButton = this.createImage(this.body, 'mmogame-button-helo',
                left, top, this.iconSize, this.iconSize, 'assets/help.png');
            helpButton.alt = 'Help';
        }

        /**
         * Finds the best value based on a condition.
         * @param {number} low - The lower bound.
         * @param {number} high - The upper bound.
         * @param {function} condition - A condition to evaluate.
         */
        findbest(low, high, condition) {
            let steps = 0;
            while (high - low > 1) {
                steps++;
                const mid = Math.floor((low + high) / 2);
                if (condition(mid)) {
                    high = mid;
                } else {
                    low = mid;
                }
            }
            console.log("findBest steps=" + steps);

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
            let instance = this;

            colors.sort(function(a, b) {
                return instance.getContrast(a) - instance.getContrast(b);
            });
            for (let i = 0; i < 5; i++) {
                ctx.fillStyle = this.getColorHex(colors[i]);
                ctx.fillRect(i * strip, 0, (i + 1) * strip, height);
            }

            ctx.strokeStyle = "#FFFFFF";
            ctx.lineWidth = 1;
            ctx.strokeRect(0, 0, width, height);
        }

        setColorsString(s) {
            let a = [0x9B7ED9, 0x79F2F2, 0x67BF5A, 0xD0F252, 0xBF5B21];
            if (s !== undefined && s.length >= 0) {
                let b = s.split(",");
                if (b.length === 5) {
                    a = b;
                    for (let i = 0; i < 5; i++) {
                        a[i] = parseInt(a[i]);
                    }
                }
            }
            this.setColors(a);
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

        createImageButton(parent, classnames, left, top, width, height, filename, wrap, alt) {
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


        createDivMessageDo(classnames, left, top, width, height, message, heightmessage) {
            if (this.divMessageBackground === undefined) {
                let div = this.createDiv(this.body, classnames, left, top, width, height);
                div.style.background = this.getColorHex(this.colorDefinition);
                this.divMessageBackground = div;
            }

            if (this.divMessage === undefined) {
                let div = document.createElement("div");
                div.style.position = "absolute";
                div.style.left = left + "px";
                div.style.textAlign = "center";
                div.style.width = (width - 2 * this.padding) + "px";
                div.style.paddingLeft = this.padding + "px";
                div.style.paddingRight = this.padding + "px";

                div.style.background = this.getColorHex(this.colorDefinition);
                div.style.color = this.getContrastingColor(this.colorDefinition);
                this.divMessage = div;
            }
            this.divMessage.innerHTML = message;
            this.body.appendChild(this.divMessage);
            this.autoResizeText(this.divMessage, width, heightmessage, false, this.minFontSize, this.maxFontSize, 0.5);
        }
        /**
         * Retrieves user options from IndexedDB.
         * @returns {Promise<Object>} A promise that resolves with the options.
         */
        getOptions() {
            return new Promise((resolve, reject) => {
                const request = indexedDB.open('mmoGameDB', 1);

                request.onupgradeneeded = function(event) {
                    const db = event.target.result;
                    if (!db.objectStoreNames.contains('options')) {
                        db.createObjectStore('options', {keyPath: 'name'});
                    }
                };

                request.onsuccess = function(event) {
                    const db = event.target.result;
                    const transaction = db.transaction(['options'], 'readonly');
                    const store = transaction.objectStore('options');

                    const getAllRequest = store.getAll();

                    getAllRequest.onsuccess = function(event) {
                        resolve(event.target.result.reduce((acc, item) => {
                            acc[item.name] = item.value;
                            return acc;
                        }, {}));
                    };

                    getAllRequest.onerror = function() {
                        reject(new Error('Failed to retrieve options'));
                    };
                };

                request.onerror = function() {
                    reject(new Error('Failed to open database'));
                };
            });
        }

        /**
         * Saves user options to IndexedDB.
         * @param {Object} options - The options to save.
         * @returns {Promise<void>} A promise that resolves when the save is complete.
         */
        setOptions(options) {
            return new Promise((resolve, reject) => {
                const request = indexedDB.open('mmoGameDB', 1);

                request.onsuccess = function(event) {
                    const db = event.target.result;
                    const transaction = db.transaction(['options'], 'readwrite');
                    const store = transaction.objectStore('options');

                    Object.entries(options).forEach(([key, value]) => {
                        store.put({name: key, value});
                    });

                    transaction.oncomplete = function() {
                        resolve();
                    };

                    transaction.onerror = function() {
                        reject(new Error('Failed to save options'));
                    };
                };

                request.onerror = function() {
                    reject(new Error('Failed to open database'));
                };
            });
        }

    clearDB(url) {
        let options = {nickname: ''};
        this.setOptions(options)
            .then(function() {
                window.location.href = url;
                return true;
            })
            .catch(() => {
                return false;
            });
        }

    };
});