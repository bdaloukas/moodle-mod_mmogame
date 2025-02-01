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
        avatarsSrc = [];
        nickNames = [];

        // Form fields
        edtNickname;

        // Gate variables
        mmogameid;

        constructor() {
            super();
            this.isVertical = window.innerWidth < window.innerHeight;
        }

        /**
         * Returns the appropriate file for mute/unmute.
         * @returns {string} The file path.
         */
        getMuteFile() {
            return this.kindSound === 0 ? 'assets/sound-on-flat.png' : 'assets/sound-off-flat.png';
        }

        /**
         * Plays a sound file if sound is enabled.
         *
         * @param {HTMLAudioElement} audioElement - The audio element to play.
         */
        playAudio(audioElement) {
            if (this.kindSound !== 0 && audioElement) {
                if (audioElement.networkState === 1) {
                    audioElement.play();
                }
            }
        }

        createButtonSound(left, top, size) {
            this.buttonSound = this.createDOMElement('img', {
                parent: this.body,
                classnames: 'mmogame-button-sound',
                styles: {
                    position: 'absolute',
                    left: `${left}px`,
                    top: `${top}px`,
                    width: `${size}px`,
                    height: `${size}px`,
                },
                attributes: {
                    src: this.getMuteFile(),
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
            button.src = this.getMuteFile();
            this.setOptions({kindSound: this.kindSound});
        }

        /**
         * Opens the gate UI, computes sizes, and initializes settings based on the user type.
         * @param {number} mmogameid - The game ID.
         * @param {string} pin - The game PIN.
         * @param {string} kinduser - The type of user (e.g., "moodle" or "guid").
         * @param {string} user - The user identifier.
         * @param {string} url - The game URL.
         */
        gateOpen(mmogameid, pin, kinduser, user, url) {
            const instance = this;

            try {
                // Initialize class variables
                this.url = url;
                this.mmogameid = mmogameid;
                this.pin = pin;
                this.kinduser = kinduser;
                instance.user = user;

                // Adjust font sizes
                this.minFontSize *= 2;
                this.maxFontSize *= 2;

                // Compute sizes and layout
                this.gateComputeSizes();
                this.areaRect = {
                    left: this.padding,
                    top: this.padding,
                    width: Math.round(window.innerWidth - 2 * this.padding),
                    height: Math.round(window.innerHeight - 2 * this.padding),
                };

                // Load options and initialize UI
                this.getOptions()
                    .then((options) => {
                        // Set default options if undefined
                        options.kindsound = options.kindsound || 0;
                        options.nickname = options.nickname || '';
                        options.avatarid = options.avatarid || 0;
                        options.paletteid = options.paletteid || 0;

                        // Assign kindSound within valid range
                        this.kindSound = [1, 2].includes(options.kindSound) ? options.kindSound : 0;

                        const isReady = options.nickname && options.avatarid && options.paletteid;

                        if (kinduser === 'moodle' && isReady) {
                            this.gatePlayGame(false, options.nickname, options.paletteid, options.avatarid);
                        } else if (kinduser === 'guid') {
                            options.userGUID = options.userGUID || '';

                            if (options.userGUID.length >= 10 && isReady) {
                                instance.user = options.userGUID;
                                this.gatePlayGame(false, options.nickname, options.paletteid, options.avatarid);
                            } else {
                                this.gateCreateScreen();
                            }
                        } else {
                            this.gateCreateScreen();
                        }

                        return true;
                    })
                    .catch((error) => {
                        this.showError('gateOpen unexpected', error);
                    });
            } catch (error) {
               this.showError('gateOpen', error);
            }
        }

        gatePlayGame(save, nickname, paletteid, avatarid) {
            let instance = this;

            if (instance.kinduser === 'guid' && instance.user === '') {
                this.uuid4();
            }

            if (!save) {
                instance.nickname = nickname;
                instance.paletteid = paletteid;
                instance.avatarid = avatarid;
                instance.callGetAttempt({nickname: nickname, colorpaletteid: paletteid, avatarid: avatarid});
                return;
            }

            let options = {nickname: nickname, avatarid: avatarid, paletteid: paletteid};

            this.setOptions(options)
                .then(() => {
                    return true;
                })
                .catch(error => {
                    this.showError(error.message);
                    return false;
                });

            this.nickname = nickname;
            this.paletteid = paletteid;
            this.avatarid = avatarid;
            this.callGetAttempt();
        }

        gateCreateScreen() {
            this.createArea();

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
            const instance = this;

            let top = this.gateCreateNickName(0, maxWidth) + this.padding;
            this.edtNickname.focus();
            // Creates the "nickname" field.

            // Palette
            const [lblPalette, btnPalette] = instance.gateCreateLabelRefresh(top, instance.getStringM('js_palette'),
                'mmogame-gate-palette-label', 'mmogame-gate-palette-refresh', 'assets/refresh.svg');
            top += lblPalette.scrollHeight + instance.padding;
            const topGridPalette = top;
            let gridHeightPalette = (maxHeight - topGridPalette - lblPalette.scrollHeight) * 2 / 5;
            const countX = Math.floor((maxWidth - this.padding) / this.iconSize);
            const countYpalette = Math.floor(gridHeightPalette / this.iconSize);
            gridHeightPalette = countYpalette * instance.iconSize;
            top += gridHeightPalette + this.padding;
            // Label Avatars
            const [lblAvatars, btnAvatars] = instance.gateCreateLabelRefresh(top, instance.getStringM('js_avatars'),
                'mmogame-gate-avatars-label', 'mmogame-gate-avatars-refresh', 'assets/refresh.svg');

            top += lblAvatars.scrollHeight + instance.padding;

            const countYavatars = Math.floor(Math.floor(maxHeight - top - this.padding) / this.iconSize);
            const gridHeightAvatars = countYavatars * this.iconSize;

            instance.addEventListenerRefresh(btnPalette, topGridPalette, countX, countYpalette,
                top, countX, countYavatars, true, false);

            instance.addEventListenerRefresh(btnAvatars, topGridPalette, countX, countYpalette,
                top, countX, countYavatars, false, true);

            // Horizontal
            instance.gateSendGetColorsAvatars(0, topGridPalette, countX, countYpalette,
                0, top, countX, countYavatars, true, true);

            this.gateCreateSubmit(top + gridHeightAvatars + 2 * this.padding, maxWidth);
        }

        gateCreateNickName(top, maxWidth) {
            const lblNickName = this.createDOMElement('label', {
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
            lblNickName.innerHTML = this.getStringM('js_name') + ": ";

            if (this.isVertical) {
                top += lblNickName.scrollHeight + this.padding;
            }

            const leftEdit = this.isVertical ? 0 : lblNickName.scrollWidth + this.padding;
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
            top += this.padding + (this.isVertical ? this.fontSize : Math.max(lblNickName.scrollHeight, this.fontSize));

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
                this.gatePlayGame(true, this.edtNickname.value, this.paletteid, this.avatarid);
            });
        }

        gateComputeLabelSize(fontSize, aLabel) {
            const instance = this;
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
                instance.area.appendChild(label);

                if (label.scrollWidth > maxWidth) {
                    maxWidth = label.scrollWidth;
                }

                if (label.scrollHeight > maxHeight) {
                    maxHeight = label.scrollHeight;
                }
                instance.area.removeChild(label);
            }

            return [maxWidth, maxHeight];
        }

        gateShowAvatars(left, top, countX, countY, avatarids, avatars) {
            const instance = this;
            if (!avatars || avatars.length === 0) {
                return; // Exit early if no avatars exist
            }

            // Delete all previous avatar icons.
            const elements = document.querySelectorAll('.mmogame-avatar');
            elements.forEach(element => element.remove());

            const fragment = document.createDocumentFragment();

            instance.avatar = undefined;
            const count = avatars.length;
            let leftOriginal = left;
            let w = Math.round(this.padding / 2) + "px";
            for (let i = 0; i < count; i++) {
                let avatarImagePath = 'assets/avatars/' + avatars[i];
                let btn = instance.createCenterImageButton(
                    fragment,
                    left, top,
                    instance.iconSize - instance.padding, instance.iconSize - instance.padding,
                    'mmogame-avatar',
                    avatarImagePath
                );
                btn.classList.add("mmogame-avatar");
                let id = avatarids[i];
                btn.addEventListener("click", () => {
                    instance.gateUpdateAvatar(btn, id, w);
                });

                // Move left position after placing the button
                left += instance.iconSize;

                // Reset left and move to the next row after filling countX buttons
                if ((i + 1) % countX === 0) {
                    top += instance.iconSize;
                    left = leftOriginal;
                }
            }
            instance.area.appendChild(fragment);
        }

        gateSendGetColorsAvatars(leftPalette, topPalette, countXpalette, countYpalette,
                                 leftAvatars, topAvatars, countXavatars, countYavatars,
                                 updatePalette = true, updateAvatars = true) {
            const instance = this;

            require(['core/ajax'], (Ajax) => {
                // Defining the parameters to be passed to the service
                let params = {
                    mmogameid: instance.mmogameid,
                    kinduser: instance.kinduser,
                    user: instance.user,
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
                        instance.gateShowColorPalettes(leftPalette, topPalette, countXpalette, countYpalette,
                            colorpaletteids, colorpalettes);
                    }
                    if (updateAvatars) {
                        instance.gateShowAvatars(leftAvatars, topAvatars, countXavatars, countYavatars,
                            avatarids, avatars);
                    }
                }).fail((error) => {
                    return error;
                });
            });
        }

        gateShowColorPalettes(left, top, countX, countY, colorpaletteids, colorpalettes) {
            const instance = this;
            let i = 0; // Counter for color palettes
            const count = colorpalettes.length;
            this.canvasColor = undefined;
            const canvasSize = instance.iconSize - instance.padding * 3 / 2;
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
                    canvas.style.left = `${left + ix * instance.iconSize}px`;
                    canvas.style.top = `${top + iy * instance.iconSize}px`;
                    canvas.width = canvasSize;
                    canvas.height = canvasSize;
                    canvas.style.cursor = 'pointer';
                    canvas.classList.add("mmogame_color");

                    // Append canvas to the area
                    fragment.appendChild(canvas);

                    // Render the color palette on the canvas
                    instance.showColorPalette(canvas, parsedPalettes[i]);

                    // Get the palette ID and attach a click event listener
                    let id = colorpaletteids[i];
                    canvas.addEventListener("click", () => {
                        instance.gateUpdateColorPalette(canvas, id);
                    });

                    i++;
                }
            }
            instance.area.appendChild(fragment);
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
            const instance = this;

            if (instance.avatar !== undefined) {
                instance.avatar.style.borderStyle = "none";
            }
            instance.avatar = avatar;
            avatar.style.borderStyle = "outset";

            avatar.style.borderLeftWidth = w;
            avatar.style.borderTopWidth = w;
            avatar.style.borderRightWidth = w;
            avatar.style.borderBottomWidth = w;

            instance.avatarid = id;

            instance.gateUpdateSubmit();
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
            const instance = this;

            instance.computeSizes();
            instance.iconSize = Math.round(0.8 * instance.iconSize);
            instance.padding = Math.round(0.8 * instance.padding);
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

        createArea() {
            if (this.area) {
                this.body.removeChild(this.area);
            }
console.log( this.areaRect);
            this.area = this.createDiv(
                this.body,
                'mmogame-area',
                this.padding,
                this.areaRect.top,
                this.areaRect.width,
                this.areaRect.height
            );
        }

        createDivMessage(classnames, message) {
            const instance = this;

            if (instance.area !== undefined) {
                instance.body.removeChild(instance.area);
                instance.area = undefined;
            }

            if (instance.divMessageHelp !== undefined) {
                instance.body.removeChild(instance.divMessageHelp);
                instance.divMessageHelp = undefined;
            }

            let left = instance.padding;
            let top = instance.areaRect !== undefined ? instance.areaRect.top : 0;
            let width = window.innerWidth - 2 * instance.padding;
            let height = window.innerHeight - instance.getCopyrightHeight() - instance.padding - top;

            instance.createDivMessageDo(classnames, left, top, width, height, message, height);

            instance.divMessage.style.top = (height - instance.divMessage.scrollHeight) / 2 + "px";
        }

        createNicknameAvatar(prefixclassname, left, topNickName, widthNickname, heightNickname, topAvatar, widthAvatar) {
            const nickname = this.createDOMElement('div', {
                parent: this.body,
                classname: `${prefixclassname}-nickname`,
                styles: {
                    left: `${left}px`,
                    top: `${topNickName}pc`,
                    width: `${widthNickname}px`,
                }
            });

            const avatar = this.createDOMElement('img', {
                classname: `${prefixclassname}-avatar`,
                parent: this.body,
                styles: {
                    left: `${left}px`,
                    top: `${topAvatar}px`,
                    width: `${widthAvatar}px`,
                }
            });


            return [nickname, avatar];
        }


        createDivMessageStart(message) {
            const instance = this;

            if (instance.area !== undefined) {
                instance.body.removeChild(instance.area);
                instance.area = undefined;
            }

            let left = instance.padding;
            let top = instance.areaRect.top;
            let width = window.innerWidth - 2 * instance.padding;
            let height = window.innerHeight - instance.getCopyrightHeight() - instance.padding - top;

            let height1 = height / 8;

            instance.createDivMessageDo('mmogame-message-start', left, top, width, height, message, height1);

            top += (height1 - instance.divMessage.scrollHeight) / 2;
            instance.divMessage.style.top = top + "px";

            if (instance.divMessageHelp === undefined) {
                let div = document.createElement("div");
                div.style.position = "absolute";
                div.style.left = left + "px";
                div.style.textAlign = "left";
                div.style.width = (width - 2 * this.padding) + "px";
                div.style.paddingLeft = this.padding + "px";
                div.style.paddingRight = this.padding + "px";

                div.style.color = instance.getContrastingColor(this.colorBackground2);
                let top = instance.iconSize + 3 * instance.padding + height1;
                div.style.top = (top + instance.padding) + "px";
                div.style.height = (height - height1) + "px";
                instance.divMessageHelp = div;
                instance.body.appendChild(instance.divMessageHelp);

                instance.showHelpScreen(div, (width - 2 * instance.padding), (height - height1));
            }
        }

        updateButtonsAvatar(num, avatarElement, nickNameElement, avatarSrc, nickname, nicknameWidth, nicknameHeight) {
            if (avatarSrc === undefined) {
                avatarSrc = "";
            }
            if (nickname === undefined) {
                nickname = "";
            }

            if (avatarSrc === "" && nickname === "") {
                avatarElement.style.visibility = 'hidden';
                nickNameElement.style.visibility = 'hidden';
                return;
            }

            if (this.nickNames[num] !== nickname || nickname === "") {
                this.nickNames[num] = nickname;
                let s = nickname;

                if (nickname.length === 0) {
                    const filenameWithExt = avatarSrc.split('/').pop(); // Extract file name
                    // Remove extension, fallback if no extension
                    s = filenameWithExt.split('.').slice(0, -1).join('.') || filenameWithExt;
                }
                s = this.repairNickname(s);
                nickNameElement.innerHTML = s;
                nickNameElement.style.textAlign = "center";
                nickNameElement.style.color = this.getContrastingColor(this.colorBackground);
                this.autoResizeText(nickNameElement, nicknameWidth, nicknameHeight, true, 0, 0);
            }

            if (avatarSrc !== this.avatarsSrc[num]) {
                avatarElement.src = avatarSrc !== "" ? "assets/avatars/" + avatarSrc : "";
                this.avatarsSrc[num] = avatarSrc;
            }

            avatarElement.alt = nickNameElement.innerHTML;
            avatarElement.style.visibility = 'visible';

            nickNameElement.style.visibility = 'visible';
        }

        /**
         * Calls the Moodle Web Service 'mmogametype_quiz_get_attempt' and processes the response.
         *
         * @param {Object} extraparams - Additional parameters to override default ones.
         */
        callGetAttempt(extraparams = undefined) {
            require(['core/ajax'], (Ajax) => {
                let params = {
                    mmogameid: this.mmogameid,
                    kinduser: this.kinduser,
                    user: this.user,
                    nickname: null,
                    colorpaletteid: null,
                    avatarid: null,
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
                    if (extraparams !== undefined && extraparams.colorpaletteid !== undefined) {
                        this.openGame();
                        this.colors = undefined;
                    }
                    this.processGetAttempt(JSON.parse(response));
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
};
});