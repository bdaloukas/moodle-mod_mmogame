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

define(['mod_mmogame/mmogame'], function(MmoGame) {
    return class MmoGameUI extends MmoGame {

        isVertical;
        kindSound; // Type: Number (0 = on, 1 = off, 2 = speak)
        buttonSound;
        colorBackground2;

        // Other
        nickname;
        user;
        paletteid;
        avatarid;
        iconSize;
        padding;

        // Area
        area;
        areaRect;

        // Form fields
        edtNickname;

        // Gate variables
        mmogameid;

        // Messages
        divMessage;
        divMessageHelp;
        divMessageBackground;

        constructor() {
            super();
            this.isVertical = window.innerWidth < window.innerHeight;
            this.area = undefined;
        }

        /**
         * Returns the appropriate file for mute/unmute.
         * @returns {string} The file path.
         */
        getSoundFile() {
            return this.kindSound === 0 ? 'assets/sound-on-flat.png' : 'assets/sound-off-flat.png';
        }

        /**
         * Plays a sound file if sound is enabled.
         *
         * @param {HTMLAudioElement} audioElement - The audio element to play.
         */
        async playSound(audioElement) {
            if (this.kindSound !== 0 && audioElement) {
                try {
                    await audioElement.play();
                } catch (error) {
                    this.showError("Playback failed:", error);
                }
            }
        }

        createButtonSound(parent, left, top) {
            this.buttonSound = this.createDOMElement('img', {
                parent: parent,
                classnames: 'mmogame-button-sound',
                styles: {
                    position: 'absolute',
                    left: `${left}px`,
                    top: `${top}px`,
                    width: `${this.iconSize}px`,
                    height: `${this.iconSize}px`,
                },
                attributes: {
                    src: this.getSoundFile(),
                    alt: this.getStringM('js_sound'),
                    role: 'button',
                },
            });
            this.buttonSound.addEventListener("click", () => this.onClickSound(this.buttonSound));
        }

        /**
         * Toggles sound on or off when clicked.
         * @param {HTMLElement} button - The sound toggle button.
         */
        onClickSound(button) {
            this.kindSound = (this.kindSound + 1) % 2;
            button.src = this.getSoundFile();
            this.setOption('kindSound', {value: this.kindSound});
        }

        /**
         * Opens the gate UI, computes sizes, and initializes settings based on the user type.
         * @param {number} mmogameid - The game ID.
         * @param {string} pin - The game PIN.
         * @param {string} kinduser - The type of user (e.g., "moodle" or "guid").
         * @param {string} user - The user identifier.
         * @param {string} url - The game URL.
         */
        async gateOpen(mmogameid, pin, kinduser, user, url) {
            this.url = url;
            this.mmogameid = mmogameid;
            this.pin = pin;
            this.kinduser = kinduser;
            this.user = user;

            if (this.kinduser === 'guid') {
                const option = await this.getOption('guid' + mmogameid);
                if (option === null) {
                    this.user = crypto.randomUUID();
                    this.setOption('guid' + mmogameid, {value: this.user});
                } else {
                    this.user = option.value;
                }
            }
            await this.callGetAttempt();
        }

        gateCreateScreen() {
            while (this.body.firstChild) {
                this.body.removeChild(this.body.firstChild);
            }
            this.area = undefined;

            this.gateCompute();

            let maxHeight = this.areaRect.height - 5 * this.padding - this.iconSize;
            let maxWidth = this.areaRect.width;
            let size;
            const labels = [
                `${this.getStringM('js_name')}: `,
                this.getStringM('js_code'),
                this.getStringM('js_palette')
            ];
            this.fontSize = this.findbest(this.minFontSize, this.maxFontSize, (fontSize) => {
                size = this.gateComputeLabelSize(fontSize, labels);

                if (size[0] >= maxWidth) {
                    return 1;
                }

                const heightColors = (maxHeight - 4 * fontSize) * 2 / 5;
                let n = Math.floor(heightColors / this.iconSize);
                if (n === 0) {
                    return 1;
                }
                const heightAvatars = (maxHeight - 4 * fontSize + heightColors) * 3 / 5;
                const computedHeight = 3 * size[1] + 8 * this.padding + heightColors + heightAvatars;

                return computedHeight < maxHeight ? -1 : 1;
            });

            this.gateCreateScreenDo(maxWidth, maxHeight);
        }

        gateCreateScreenDo(maxWidth, maxHeight) {
            // Creates the "nickname" field.
            let top = this.gateCreateNickname(0, maxWidth) + this.padding;
            this.edtNickname.focus();

            // Palette
            const [lblPalette, btnPalette] = this.gateCreateLabelRefresh(top, this.getStringM('js_palette'),
                'mmogame-gate-palette-label', 'mmogame-gate-palette-refresh', 'assets/refresh.svg');
            top += lblPalette.scrollHeight + this.padding;
            const topGridPalette = top;
            let gridHeightPalette = (maxHeight - topGridPalette - lblPalette.scrollHeight) * 2 / 5;
            const countX = Math.floor((maxWidth - this.padding) / this.iconSize);
            const countYpalette = Math.floor(gridHeightPalette / this.iconSize);
            gridHeightPalette = countYpalette * this.iconSize;
            top += gridHeightPalette + this.padding;
            // Label Avatars
            const [lblAvatars, btnAvatars] = this.gateCreateLabelRefresh(top, this.getStringM('js_avatars'),
                'mmogame-gate-avatars-label', 'mmogame-gate-avatars-refresh', 'assets/refresh.svg');

            top += lblAvatars.scrollHeight + this.padding;

            const countYavatars = Math.floor(Math.floor(maxHeight - top - this.padding) / this.iconSize);
            const gridHeightAvatars = countYavatars * this.iconSize;

            this.addEventListenerRefresh(btnPalette, topGridPalette, countX, countYpalette,
                top, countX, countYavatars, true, false);

            this.addEventListenerRefresh(btnAvatars, topGridPalette, countX, countYpalette,
                top, countX, countYavatars, false, true);

            // Horizontal
            this.gateSendGetColorsAvatars(0, topGridPalette, countX, countYpalette,
                0, top, countX, countYavatars, true, true);

            this.gateCreateSubmit(top + gridHeightAvatars + 2 * this.padding, maxWidth);
        }

        gateCreateNickname(top, maxWidth) {
            const lblNickname = this.createDOMElement('label', {
                parent: this.area,
                classnames: 'mmogame-gate-name-label',
                styles: {
                    position: 'absolute',
                    fontSize: `${this.fontSize}px`,
                    left: '0',
                    top: `${top}px`,
                    width: '0',
                    color: this.getContrastingColor(this.colorBackground),
                },
            });
            lblNickname.innerHTML = this.getStringM('js_name') + ": ";

            if (this.isVertical) {
                top += lblNickname.scrollHeight + this.padding;
            }

            const leftEdit = this.isVertical ? 0 : lblNickname.scrollWidth + this.padding;
            const width = this.isVertical ? maxWidth : maxWidth - 2 * this.padding;
            this.edtNickname = this.createDOMElement('input', {
                parent: this.area,
                classnames: 'mmogame-gate-name',
                styles: {
                    position: 'absolute',
                    fontSize: `${this.fontSize}px`,
                    left: `${leftEdit}px`,
                    top: `${top}px`,
                    width: `${width - leftEdit - this.padding}px`
                },
            });
            this.edtNickname.addEventListener("keyup", this.debounce(() => this.gateUpdateSubmit(), 300));
            top += this.padding + (this.isVertical ? this.fontSize : Math.max(lblNickname.scrollHeight, this.fontSize));

            return top;
        }

        gateCreateSubmit(top, maxWidth) {
           this.btnSubmit = this.createDOMElement('img', {
                parent: this.area,
                classnames: 'mmogame-button-gate-submit',
                styles: {
                    position: 'absolute',
                    fontSize: `${this.fontSize}px`,
                    left: `${(maxWidth - this.iconSize) / 2}px`,
                    top: `${top}px`,
                    height: `${this.iconSize}px`,
                    color: this.getContrastingColor(this.colorBackground),
                    cursor: 'pointer',
                    visibility: 'hidden',
                },
                attributes: {
                    src: 'assets/submit.svg',
                }
            });
            this.btnSubmit.addEventListener("click", () => {
                // This.gatePlayGame(true, this.edtNickname.value, this.paletteid, this.avatarid);
                this.callGetAttempt(
                    {nickname: this.edtNickname.value, colorpaletteid: this.paletteid, avatarid: this.avatarid},
                );
            });
        }

        gateComputeLabelSize(fontSize, aLabel) {
            let maxWidth = 0;
            let maxHeight = 0;

            for (let i = 0; i < aLabel.length; i++) {
                const label = document.createElement("label");
                label.style.position = "absolute";
                label.innerHTML = aLabel[i];
                label.style.whiteSpace = "nowrap";
                label.style.font = "FontAwesome";
                label.style.fontSize = fontSize + "px";
                label.style.width = "0px";
                label.style.height = "0px";
                this.area.appendChild(label);

                if (label.scrollWidth > maxWidth) {
                    maxWidth = label.scrollWidth;
                }

                if (label.scrollHeight > maxHeight) {
                    maxHeight = label.scrollHeight;
                }
                this.area.removeChild(label);
            }

            return [maxWidth, maxHeight];
        }

        gateShowAvatars(left, top, countX, countY, avatarids, avatars) {
            if (!avatars || avatars.length === 0) {
                return; // Exit early if no avatars exist
            }

            // Delete all previous avatar icons.
            const elements = document.querySelectorAll('.mmogame-avatar');
            elements.forEach(element => element.remove());

            const fragment = document.createDocumentFragment();

            this.avatar = undefined;
            const count = avatars.length;
            let leftOriginal = left;
            let w = Math.round(this.padding / 2) + "px";
            for (let i = 0; i < count; i++) {
                let avatarImagePath = 'assets/avatars/' + avatars[i];
                let btn = this.createCenterImageButton(
                    fragment,
                    left, top,
                    this.iconSize - this.padding, this.iconSize - this.padding,
                    'mmogame-avatar',
                    avatarImagePath
                );
                btn.classList.add("mmogame-avatar");
                let id = avatarids[i];
                btn.addEventListener("click", () => {
                    this.gateUpdateAvatar(btn, id, w);
                });

                // Move left position after placing the button
                left += this.iconSize;

                // Reset left and move to the next row after filling countX buttons
                if ((i + 1) % countX === 0) {
                    top += this.iconSize;
                    left = leftOriginal;
                }
            }
            this.area.appendChild(fragment);
        }

        gateSendGetColorsAvatars(leftPalette, topPalette, countXpalette, countYpalette,
                                 leftAvatars, topAvatars, countXavatars, countYavatars,
                                 updatePalette = true, updateAvatars = true) {
            require(['core/ajax'], (Ajax) => {
                // Defining the parameters to be passed to the service
                let params = {
                    mmogameid: this.mmogameid,
                    kinduser: this.kinduser,
                    user: this.user,
                    avatars: updateAvatars ? countXavatars * countYavatars : 0,
                    colorpalettes: updatePalette ? countXpalette * countYpalette : 0,
                };
                // Calling the service through the Moodle AJAX API
                let getAssets = Ajax.call([{
                    methodname: 'mod_mmogame_get_assets',
                    args: params
                }]);

                // Handling the response
                getAssets[0].done(({avatarids, avatars, colorpaletteids, colorpalettes}) => {
                    if (updatePalette) {
                        this.gateShowColorPalettes(leftPalette, topPalette, countXpalette, countYpalette,
                            colorpaletteids, colorpalettes);
                    }
                    if (updateAvatars) {
                        this.gateShowAvatars(leftAvatars, topAvatars, countXavatars, countYavatars,
                            avatarids, avatars);
                    }
                }).fail((error) => {
                    return error;
                });
            });
        }

        gateShowColorPalettes(left, top, countX, countY, colorpaletteids, colorpalettes) {
            let i = 0; // Counter for color palettes
            const count = colorpalettes.length;
            this.canvasColor = undefined;
            const canvasSize = this.iconSize - this.padding * 3 / 2;
            const parsedPalettes = colorpalettes.map(palette =>
                palette.split(",").map(value => parseInt(value, 10) || 0)
            );
            const fragment = document.createDocumentFragment();
            for (let iy = 0; iy < countY; iy++) {
                for (let ix = 0; ix < countX; ix++) {
                    // Check if we exceed available palettes or encounter invalid data
                    if (i >= count || !parsedPalettes[i] || !colorpaletteids[i]) {
                        i++; // Increment and continue if invalid
                        continue;
                    }

                    // Create a new canvas element
                    let canvas = document.createElement('canvas');
                    canvas.style.position = "absolute";
                    canvas.style.left = `${left + ix * this.iconSize}px`;
                    canvas.style.top = `${top + iy * this.iconSize}px`;
                    canvas.width = canvasSize;
                    canvas.height = canvasSize;
                    canvas.style.cursor = 'pointer';
                    canvas.classList.add("mmogame_color");

                    // Append canvas to the area
                    fragment.appendChild(canvas);

                    // Render the color palette on the canvas
                    this.showColorPalette(canvas, parsedPalettes[i]);

                    // Get the palette ID and attach a click event listener
                    let id = colorpaletteids[i];
                    canvas.addEventListener("click", () => {
                        this.gateUpdateColorPalette(canvas, id);
                    });

                    i++;
                }
            }
            this.area.appendChild(fragment);
        }

        gateUpdateColorPalette(canvas, id) {
            if (this.canvasColor !== undefined) {
                this.canvasColor.style.borderStyle = "none";
            }
            this.canvasColor = canvas;
            let w = Math.round(this.padding / 2) + "px";

            Object.assign(canvas.style, {
                borderStyle: "outset",
                borderLeftWidth: w,
                borderTopWidth: w,
                borderRightWidth: w,
                borderBottomWidth: w,
            });
            this.paletteid = id;

            this.gateUpdateSubmit();
        }

        gateUpdateAvatar(avatar, id, w) {
            if (this.avatar !== undefined) {
                this.avatar.style.borderStyle = "none";
            }
            this.avatar = avatar;
            avatar.style.borderStyle = "outset";

            avatar.style.borderLeftWidth = w;
            avatar.style.borderTopWidth = w;
            avatar.style.borderRightWidth = w;
            avatar.style.borderBottomWidth = w;

            this.avatarid = id;

            this.gateUpdateSubmit();
        }

        /**
         * Updates the visibility of the submit button based on form input validation.
         */
        gateUpdateSubmit() {
            const hasAvatar = this.avatarid !== undefined;
            const hasPalette = this.paletteid !== undefined;
            const hasNickname = this.edtNickname?.value?.length > 0;

            this.btnSubmit.style.visibility = hasAvatar && hasPalette && hasNickname ? 'visible' : 'hidden';
        }

        gateComputeSizes() {
            this.computeSizes();
            this.iconSize = Math.round(0.8 * this.iconSize);
            this.padding = Math.round(0.8 * this.padding);
        }

        /**
         * Creates the screen palette UI with a label and a refresh button.
         * @param {number} top - The vertical position for the elements.
         * @param {string} title
         * @param {string} classLabel
         * @param {string} classButton
         * @param {string} src
         */
        gateCreateLabelRefresh(top, title, classLabel, classButton, src) {
            // Create and configure the label
            const label = this.createDOMElement('label', {
                parent: this.area,
                classnames: classLabel,
                styles: {
                    position: 'absolute',
                    font: 'FontAwesome',
                    fontSize: `${this.fontSize}px`,
                    width: '0px',
                    whiteSpace: 'nowrap',
                    color: this.getContrastingColor(this.colorBackground),
                    top: `${top}px`,
                    left: '0px',
                },
            });
            label.innerHTML = title;

            // Button refresh color palettes
            let button = this.createDOMElement('img', {
                parent: this.area,
                classnames: classButton,
                styles: {
                    position: 'absolute',
                    fontSize: `${this.fontSize}px`,
                    left: `${label.scrollWidth + this.padding}px`,
                    top: `${top}px`,
                    height: `${label.scrollHeight}px`,
                    color: this.getContrastingColor(this.colorBackground),
                    cursor: 'pointer',
                },
                attributes: {
                    src: src,
                }
            });

            return [label, button];
        }

        /**
         * Adds an event listener to refresh colors and avatars.
         *
         * @param {HTMLElement} btn - The button to attach the event listener to.
         * @param {number} topPalette - The Y-coordinate offset for grid positioning.
         * @param {number} countXpalette - Width of the color grid.
         * @param {number} countYpalette - Height of the color grid.
         * @param {number} topAvatars - The Y-coordinate offset for grid positioning.
         * @param {number} countXavatars - Width of the avatar grid.
         * @param {number} countYavatars - Height of the avatar grid.
         * @param {boolean} updateColors - Callback to update colors.
         * @param {boolean} updateAvatars - Callback to update avatars.
         */
        addEventListenerRefresh(btn, topPalette, countXpalette, countYpalette, topAvatars,
                                countXavatars, countYavatars, updateColors, updateAvatars) {
            btn.addEventListener("click", () => {
                const elements = Array.from(this.area.getElementsByClassName("mmogame-color"));
                elements.forEach(element => element.remove());

                this.gateSendGetColorsAvatars(0, topPalette, countXpalette, countYpalette,
                    0, topAvatars, countXavatars, countYavatars,
                    updateColors, updateAvatars);
            });
        }

        /**
         * Creates the main game area.
         */

        createArea(top, bottomSpace) {
            if (this.area !== undefined) {
                this.body.removeChild(this.area);
            }
            this.area = this.createDOMElement('div', {
                parent: this.body,
                classnames: 'mmogame-area',
                styles: {
                    position: 'absolute',
                    left: `${this.padding}px`,
                    top: `${top}px`,
                    right: `${this.padding}px`,
                    bottom: `${this.padding + bottomSpace}px`,
                    overflow: 'hidden',
                }
            });

            this.areaRect = {
                left: this.padding,
                top: top,
                width: this.area.offsetWidth,
                height: this.area.offsetHeight,
                bottom: bottomSpace,
            };
        }

        removeAreaChildren() {
            if (this.area === undefined) {
                return;
            }
            while (this.area.firstChild) {
                this.area.removeChild(this.area.firstChild);
            }
        }

        createDivMessage(classnames, message) {
            if (this.area !== undefined) {
                this.body.removeChild(this.area);
                this.area = undefined;
            }

            if (this.divMessageHelp !== undefined) {
                this.body.removeChild(this.divMessageHelp);
                this.divMessageHelp = undefined;
            }

            let left = this.padding;
            let top = this.areaRect !== undefined ? this.areaRect.top : 0;
            let width = window.innerWidth - 2 * this.padding;
            let height = window.innerHeight - this.padding - top;

            this.createDivMessageDo(classnames, left, top, width, height, message, height);

            this.divMessage.style.top = (height - this.divMessage.scrollHeight) / 2 + "px";
        }

        createNicknameAvatar(parent, prefixclassname, left, topNickname, widthNickname, heightNickname, topAvatar, widthAvatar) {
            const nickname = this.createDOMElement('div', {
                parent: parent,
                classname: `${prefixclassname}-nickname`,
                styles: {
                    position: 'absolute',
                    left: `${left}px`,
                    top: `${topNickname}px`,
                    width: `${widthNickname}px`,
                }
            });

            let leftAvatar = Math.round(left + this.iconSize / 2);
            const avatar = this.createDOMElement('img', {
                classname: `${prefixclassname}-avatar`,
                parent: this.body,
                styles: {
                    position: 'absolute',
                    left: `${leftAvatar}px`,
                    top: `${topAvatar}px`,
                    height: `${widthAvatar}px`,
                    maxWidth: `${widthAvatar}px`,
                    transform: 'translateX(-50%)',
                }
            });

            return [nickname, avatar];
        }


        createDivMessageStart(message) {
            if (this.divMessageHelp !== undefined) {
                return;
            }
            if (this.area !== undefined) {
                this.body.removeChild(this.area);
                this.area = undefined;
            }

            let left = this.padding;
            let top = this.areaRect.top;
            let width = window.innerWidth - 2 * this.padding;
            let height = window.innerHeight - this.padding - top;

            let height1 = height / 8;

            this.createDivMessageDo('mmogame-message-start', left, top, width, height, message, height1);

            top += (height1 - this.divMessage.scrollHeight) / 2;
            this.divMessage.style.top = top + "px";

            let div = document.createElement("div");
            div.style.position = "absolute";
            div.style.left = left + "px";
            div.style.textAlign = "left";
            div.style.width = (width - 2 * this.padding) + "px";
            div.style.paddingLeft = this.padding + "px";
            div.style.paddingRight = this.padding + "px";

            div.style.color = this.getContrastingColor(this.colorBackground2);
            top = this.iconSize + 3 * this.padding + height1;
            div.style.top = (top + this.padding) + "px";
            div.style.height = (height - height1) + "px";
            this.divMessageHelp = div;
            this.body.appendChild(this.divMessageHelp);

            this.showHelpScreen(div, (width - 2 * this.padding), (height - height1));
        }

        /**
         * Calls the Moodle Web Service 'mmogametype_quiz_get_attempt' and processes the response.
         *
         * @param {Object} extraparams - Additional parameters to override default ones.
         */
        async callGetAttempt(extraparams = undefined) {

            if (this.kindSound === undefined) {
                const option = await this.getOption("kindSound");
                this.kindSound = option !== null ? option.value : 1;
            }

            require(['core/ajax'], (Ajax) => {
                let params = {
                    mmogameid: this.mmogameid,
                    kinduser: this.kinduser,
                    user: this.user,
                    nickname: null,
                    colorpaletteid: null,
                    avatarid: null,
                    subcommand: '',
                };
                if (extraparams !== undefined) {
                    params = {...params, ...extraparams};
                }
                // Calling the service through the Moodle AJAX API
                let getAttempt = Ajax.call([{
                    methodname: 'mmogametype_quiz_get_attempt',
                    args: params,
                }]);

                // Handling the response
                getAttempt[0].done((response) => {
                    const json = JSON.parse(response);
                    if (json.errorcode === 'no_user') {
                        this.gateCreateScreen();
                        return;
                    }
                    if (this.area !== undefined) {
                        this.body.removeChild(this.area);
                        this.area = undefined;
                    }
                    if (this.iconSize === undefined) {
                        this.openGame();
                    }

                    this.processGetAttempt(json);
                }).fail((error) => {
                    this.createDivMessage('mmogame-error', error.message);
                    return error;
                });
            });
        }

        createDivMessageDo(classnames, left, top, width, height, message, heightmessage) {
            if (this.divMessageBackground === undefined) {
                let div = this.createDiv(this.body, classnames, left, top, width, height);
                div.style.background = this.getColorHex(this.colorBackground2);
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

                div.style.background = this.getColorHex(this.colorBackground2);
                div.style.color = this.getContrastingColor(this.colorBackground2);
                this.divMessage = div;
            }
            this.divMessage.innerHTML = message;
            this.body.appendChild(this.divMessage);
            this.autoResizeText(this.divMessage, width, heightmessage, false, this.minFontSize, this.maxFontSize, 0.5);
        }

        removeMessageDivs() {
            if (this.divMessage !== undefined) {
                this.body.removeChild(this.divMessage);
                this.divMessage = undefined;
            }
            if (this.divMessageHelp !== undefined) {
                this.body.removeChild(this.divMessageHelp);
                this.divMessageHelp = undefined;
            }
            if (this.divMessageBackground !== undefined) {
                this.body.removeChild(this.divMessageBackground);
                this.divMessageBackground = undefined;
            }
        }

        setColors(colors) {
            super.setColors(colors);

            this.colorBackground2 = colors[1];
        }

        /**
         * Displays an error message on the screen.
         * @param {string} name - The name of the error context.
         * @param {Error} [error] - The error object to display.
         */
        showError(name, error) {
            const message = error?.message || 'An unknown error occurred.';
            this.createDivMessage('mmogame-error', message);
        }

        createButtonHelp(parent, left, top) {
            return this.createDOMElement('img', {
                parent: parent,
                classnames: 'mmogame-button-help',
                styles: {
                    position: 'absolute',
                    left: `${left}px`,
                    top: `${top}px`,
                    width: `${this.iconSize}px`,
                    height: `${this.iconSize}px`,
                },
                attributes: {
                    src: 'assets/help.svg',
                    alt: this.getStringM('js_help'),
                    role: 'button',
                },
            });
        }

        onClickHelp() {
            if (this.divMessageHelp === undefined) {
                this.createDivMessageStart('Help');
            } else {
                this.removeMessageDivs();
                this.callGetAttempt();
            }
        }

        gateCompute() {
            // Adjust font sizes
            this.minFontSize *= 2;
            this.maxFontSize *= 2;

            // Compute sizes and layout
            this.gateComputeSizes();
            this.createArea(this.padding, 0);
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

        hasHelp() {
            return false;
        }

        /**
         * Calls the Moodle Web Service 'mmogame_get_state' and returns the response.
         */
        async callGetState() {
            return new Promise((resolve, reject) => {
                // Calling the service through the Moodle AJAX API
                require(['core/ajax'], (Ajax) => {
                    let getState = Ajax.call([{
                        methodname: 'mod_mmogame_get_state',
                        args: {mmogameid: this.mmogameid},
                    }]);

                    getState[0]
                        .done((response) => resolve(response))
                        .fail((error) => {
                            this.createDivMessage('mmogame-error', error.message);
                            reject(error);
                        });
                });
            });
        }

    };
});