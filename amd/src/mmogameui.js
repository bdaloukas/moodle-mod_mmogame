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
        colorBackground = 0xFFFFFF;
        colorDefinition;
        colorScore;

        // Other
        definition;
        nickname;
        user;
        paletteid;
        avatarid;

        // Form fields
        edtCode;
        edtNickname;

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
                    top: this.padding,
                    width: Math.round(window.innerWidth - 2 * this.padding),
                    height: Math.round(window.innerHeight - this.areaTop - this.padding),
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
                    this.nickname = nickname;
                    this.paletteid = paletteid;
                    this.avatarid = avatarid;
                    this.callGetAttempt();
                    return true;
                })
                .catch(error => {
                    this.showError(error.message);
                    return false;
                });
        }

        gateCreateScreen() {
            this.createArea();

            if (this.isVertical) {
                this.gateCreateScreenVertical();
            } else {
                this.gateCreateScreenHorizontal();
            }
        }

        gateCreateScreenVertical() {
            const instance = this;

            let maxHeight = instance.areaRect.height - 5 * instance.padding - instance.iconSize;
            let maxWidth = instance.areaRect.width;
            let size;

            const labels = [
                `${instance.getStringM('js_name')}: `,
                instance.getStringM('js_code'),
                instance.getStringM('js_palette')
            ];

            instance.fontSize = instance.findbest(instance.minFontSize, instance.maxFontSize, (fontSize) => {
                size = instance.gateComputeLabelSize(fontSize, labels);

                if (size[0] >= maxWidth) {
                    return 1;
                }
                const heightCode = instance.kinduser !== 'guid' && instance.kinduser !== 'moodle' ?
                    size[1] + instance.padding : 0;

                const heightColors = (maxHeight - 4 * fontSize) * 2 / 5;
                let n = Math.floor(heightColors / instance.iconSize);
                if (n === 0) {
                    return 1;
                }
                const heightAvatars = (maxHeight - 4 * fontSize + heightColors) * 3 / 5;
                const computedHeight = heightCode + 3 * size[1] + 8 * instance.padding + heightColors + heightAvatars;

                return computedHeight < maxHeight ? -1 : 1;
            });

            instance.gateCreateScreenVerticalDo(maxWidth, maxHeight, size);
        }

        gateCreateScreenVerticalDo(maxWidth, maxHeight, size) {
            const instance = this;

            let gridWidthColors = maxWidth - instance.padding;
            let gridWidthAvatars = maxWidth - instance.padding;
            let gridHeightColors = (maxHeight - 4 * instance.fontSize) * 2 / 5;
            let newHeight = Math.floor(gridHeightColors / instance.iconSize) * instance.iconSize;
            let newWidth = Math.floor(gridWidthColors / instance.iconSize) * instance.iconSize;
            let rest = gridHeightColors - newHeight;
            gridHeightColors = newHeight;
            let gridHeightAvatars = (maxHeight - 4 * instance.fontSize + rest) * 3 / 5;

            // Creates the "Code" field.
            let bottom;
            if (instance.kinduser !== 'guid' && instance.kinduser !== 'moodle') {
                // A bottom = this.gateCreateCode(0, 0, maxWidth, this.fontSize, size[0]);
                bottom = instance.gateCreateLabelEditVertical(0, 0, maxWidth, instance.fontSize,
                    size[0], instance.getStringM('js_code') + ": ",
                    'mmogame-gate-code-label', 'mmogame-gate-code') + 2 * this.padding;
                instance.edtCode = instance.edt;
                instance.edtCode.addEventListener("keyup", instance.debounce(() => instance.gateUpdateSubmit(), 300));
            } else {
                bottom = 0;
            }

            bottom = instance.gateCreateLabelEditVertical(0, bottom,
                newWidth - 2 * instance.padding, instance.fontSize, size[0],
                instance.getStringM('js_name') + ": ",
                'mmogame-gate-name-label', 'mmogame-gate-name') + 2 * instance.padding;
            instance.edtNickname = instance.edt;
            instance.edtNickname.addEventListener("keyup", instance.debounce(() => instance.gateUpdateSubmit(), 300));

            instance.gateCreateScreenPalette(bottom, gridWidthColors, gridHeightColors,
                gridWidthAvatars, gridHeightAvatars);

            bottom += this.fontSize + instance.padding;

            // Vertical
            instance.gateSendGetColorsAvatars(0, bottom, gridWidthColors, gridHeightColors,
                0, bottom + gridHeightColors + instance.fontSize + instance.padding, gridWidthAvatars,
                gridHeightAvatars);

            const bottom2 = bottom + gridHeightColors + instance.fontSize + instance.padding + gridHeightAvatars;

            instance.gateCreateButtonSubmit(maxWidth, bottom2);
        }

        gateCreateScreenHorizontal() {
            const instance = this;

            let maxHeight = instance.areaHeight - 7 * instance.padding - instance.iconSize;
            let maxWidth = instance.areaWidth;
            let size;

            const sName = instance.getStringM('js_name') + ": ";
            let labels = [instance.getStringM('js_code'), sName, instance.getStringM('js_palette')];

            instance.fontSize = instance.findbest(instance.minFontSize, instance.maxFontSize, (fontSize) => {
                size = instance.gateComputeLabelSize(fontSize, labels);

                if (size[0] >= maxWidth) {
                    return 1;
                }
                let heightCode = instance.kinduser !== 'guid' && instance.kinduser !== 'moodle' ? size[1] + instance.padding : 0;

                let heightColors = (maxHeight - 4 * fontSize) * 2 / 5;
                let n = Math.floor(heightColors / instance.iconSize);
                if (n === 0) {
                    return 1;
                }
                let heightAvatars = (maxHeight - 4 * fontSize + heightColors) * 3 / 5;
                let computedHeight = heightCode + 2 * size[1] + 7 * instance.padding + heightColors + heightAvatars;

                return computedHeight < maxHeight ? -1 : 1;
            });

            instance.gateCreateScreenHorizontalDo(maxWidth, maxHeight, size, sName);
        }

        gateCreateScreenHorizontalDo(maxWidth, maxHeight, size, sName) {
            const instance = this;

            const gridWidthColors = maxWidth - instance.padding;
            const gridWidthAvatars = maxWidth - instance.padding;
            let gridHeightColors = (maxHeight - 4 * instance.fontSize) * 2 / 5;
            const newHeight = Math.floor(gridHeightColors / instance.iconSize) * instance.iconSize;
            const newWidth = Math.floor(gridWidthColors / instance.iconSize) * instance.iconSize;
            let rest = gridHeightColors - newHeight;
            gridHeightColors = newHeight;
            const gridHeightAvatars = Math.floor((maxHeight - 4 * instance.fontSize) * 3 / 5 + rest);

            // Creates the "Code" field.
            let bottom;
            if (instance.kinduser !== 'guid' && instance.kinduser !== 'moodle') {
                bottom = instance.gateCreateLabelEditVertical(0, 0, maxWidth, instance.fontSize, size[0],
                    instance.getStringM('js_code')) + 2 * instance.padding;
                instance.edtCode = instance.edt;
                instance.edtCode.addEventListener("keyup", instance.debounce(() => instance.gateUpdateSubmit(), 300));
            } else {
                bottom = 0;
            }

            // Creates the "nickname" field.
            let sizeLabel = instance.gateComputeLabelSize(instance.fontSize, [sName]);
            bottom = this.gateCreateLabelEditHorizontal(0, bottom,
                newWidth - 2 * instance.padding, instance.fontSize,
                sizeLabel[0], instance.getStringM('js_name') + ": ",
                'mmogame-gate-name-label', 'mmogame-gate-name');

            instance.edtNickname = this.edt;
            instance.edtNickname.addEventListener("keyup", instance.debounce(() => instance.gateUpdateSubmit(), 300));

            let label1 = document.createElement("label");
            label1.style.position = "absolute";
            label1.style.color = instance.getContrastingColor(instance.colorBackground);
            label1.innerHTML = instance.getStringM('js_palette');
            label1.style.font = "FontAwesome";
            label1.style.fontSize = instance.fontSize + "px";
            label1.style.width = "0px";
            label1.style.whiteSpace = "nowrap";
            instance.area.appendChild(label1);

            // Button refresh color palettes
            let btn = instance.createImageButton(instance.area, 'mmogame-button-gate-refresh',
                label1.scrollWidth + instance.padding, bottom, instance.iconSize, instance.fontSize,
                'assets/refresh.svg', false, 'refresh');
            instance.addEventListenerRefresh(btn, bottom, gridWidthColors, gridHeightColors,
                gridWidthAvatars, gridHeightAvatars, true, false);

            label1.style.left = 0;
            label1.style.color = instance.getContrastingColor(instance.colorBackground);
            label1.style.top = bottom + "px";
            bottom += instance.fontSize + instance.padding;

            let label = document.createElement("label");
            label.style.position = "absolute";
            label.innerHTML = instance.getStringM('js_avatars');
            label.style.font = "FontAwesome";
            label.style.fontSize = instance.fontSize + "px";
            label.style.width = "0 px";
            label.style.whiteSpace = "nowrap";
            instance.area.appendChild(label);

            // Button refresh avatars
            btn = instance.createImageButton(instance.area, 'mmogame-button-gate-refresh-avatars',
                label.scrollWidth + instance.padding, bottom + gridHeightColors, instance.iconSize,
                instance.fontSize, 'assets/refresh.svg', false, 'refresh');
            btn.addEventListener("click", () => {
                let elements = instance.area.getElementsByClassName("mmogame_avatar");

                while (elements[0]) {
                    elements[0].parentNode.removeChild(elements[0]);
                }

                instance.gateSendGetColorsAvatars(0, bottom, gridWidthColors, gridHeightColors, 0,
                    bottom + gridHeightColors + instance.fontSize + instance.padding, gridWidthAvatars, gridHeightAvatars,
                    false, true);
            });

            // Avatar
            label.style.left = "0 px";
            label.style.color = instance.getContrastingColor(instance.colorBackground);
            label.style.top = (bottom + gridHeightColors) + "px";

            // Horizontal
            instance.gateSendGetColorsAvatars(0, bottom, gridWidthColors, gridHeightColors,
                0, bottom + gridHeightColors + instance.fontSize + instance.padding, gridWidthAvatars,
                gridHeightAvatars);

            let bottom2 = bottom + gridHeightColors + instance.fontSize + instance.padding + gridHeightAvatars;
            instance.btnSubmit = instance.createImageButton(instance.area, 'mmogame-button-gate-submit',
                (maxWidth - instance.iconSize) / 2, bottom2, 0, instance.iconSize,
                'assets/submit.svg', false, 'submit');
            instance.btnSubmit.style.visibility = 'hidden';
            instance.btnSubmit.addEventListener("click", () => {
                if (instance.edtCode !== undefined) {
                    instance.user = instance.edtCode.value;
                }
                instance.gatePlayGame(true, this.edtNickname.value, this.paletteid, this.avatarid);
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

        gateCreateLabelEditVertical(left, top, width, fontSize, labelWidth, title, classnamesLabel, classnamesEdit) {
            const instance = this;

            const label = instance.createLabel(instance.area, classnamesLabel, left, top, labelWidth, fontSize, title);
            label.style.color = this.getContrastingColor(instance.colorBackground);

            top += label.scrollHeight;

            instance.edt = instance.gateCreateInput(classnamesEdit, left, top, width, fontSize);

            return top + fontSize + instance.padding;
        }

        gateShowAvatars(left, top, width, height, countX, avatarids, avatars) {
            const instance = this;
            if (!avatars || avatars.length === 0) {
                return; // Exit early if no avatars exist
            }

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
                btn.classList.add("mmogame_avatar");
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

        gateSendGetColorsAvatars(leftColors, topColors, gridWidthColors, gridHeightColors, leftAvatars, topAvatars,
                                 gridWidthAvatars, gridHeightAvatars, updateColors = true, updateAvatars = true) {
            const instance = this;

            let countXcolors = Math.floor(gridWidthColors / instance.iconSize);
            let countYcolors = Math.floor(gridHeightColors / instance.iconSize);

            let countXavatars = Math.floor(gridWidthAvatars / instance.iconSize);
            let countYavatars = Math.floor((gridHeightAvatars + 2 * instance.padding) / instance.iconSize);

            if (!updateColors) {
                countXcolors = countYcolors = 0;
            }
            if (!updateAvatars) {
                countXavatars = countYavatars = 0;
            }

            require(['core/ajax'], (Ajax) => {
                // Defining the parameters to be passed to the service
                let params = {
                    mmogameid: instance.mmogameid,
                    kinduser: instance.kinduser,
                    user: instance.user,
                    avatars: countXavatars * countYavatars,
                    colorpalettes: countXcolors * countYcolors,
                };

                // Calling the service through the Moodle AJAX API
                let getAssets = Ajax.call([{
                    methodname: 'mod_mmogame_get_assets',
                    args: params
                }]);

                // Handling the response
                getAssets[0].done(({avatarids, avatars, colorpaletteids, colorpalettes}) => {
                    if (updateColors) {
                        instance.gateShowColorPalettes(leftColors, topColors, gridWidthColors,
                            gridHeightColors, countXcolors, countYcolors, colorpaletteids, colorpalettes);
                    }
                    if (updateAvatars) {
                        instance.gateShowAvatars(leftAvatars, topAvatars, gridWidthAvatars, gridHeightAvatars, countXavatars,
                            avatarids, avatars);
                    }
                }).fail((error) => {
                    return error;
                });
            });
        }

        gateShowColorPalettes(left, top, width, height, countX, countY, colorpaletteids, colorpalettes) {
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
            const instance = this;

            if (instance.canvasColor !== undefined) {
                instance.canvasColor.style.borderStyle = "none";
            }
            this.canvasColor = canvas;
            let w = Math.round(instance.padding / 2) + "px";

            Object.assign(canvas.style, {
                borderStyle: "outset",
                borderLeftWidth: w,
                borderTopWidth: w,
                borderRightWidth: w,
                borderBottomWidth: w,
            });
            instance.paletteid = id;

            instance.gateUpdateSubmit();
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
            const instance = this;

            const isCodeValid = instance.edtCode?.value ? Number(instance.edtCode.value) > 0 : true;
            const hasAvatar = instance.avatarid !== undefined;
            const hasPalette = instance.paletteid !== undefined;
            const hasNickname = instance.edtNickname?.value?.length > 0;

            instance.btnSubmit.style.visibility =
                isCodeValid && hasAvatar && hasPalette && hasNickname
                    ? 'visible'
                    : 'hidden';
        }

        gateComputeSizes() {
            const instance = this;

            instance.computeSizes();
            instance.iconSize = Math.round(0.8 * instance.iconSize);
            instance.padding = Math.round(0.8 * instance.padding);
        }

        gateCreateLabelEditHorizontal(left, top, width, fontSize, labelWidth, title, classnamesLabel, classnamesEdit) {
            const instance = this;

            const label = this.createLabel(instance.area, classnamesLabel, left, top, labelWidth, fontSize, title);
            label.style.color = this.getContrastingColor(instance.colorBackground);

            let ret = top + Math.max(label.scrollHeight, fontSize) + instance.padding;

            let leftEdit = (left + labelWidth + this.padding);
            this.edt = instance.gateCreateInput(classnamesEdit, leftEdit, top, width - leftEdit - this.padding, fontSize);

            return ret;
        }

        gateCreateInput(classnames, left, top, width, fontSize) {
            const instance = this;

            const div = document.createElement("input");
            div.style.position = "absolute";
            div.style.width = width + "px";
            div.style.type = "text";
            div.style.fontSize = fontSize + "px";

            div.style.left = left + "px";
            div.style.top = top + "px";
            div.autofocus = true;

            div.classList.add(...classnames.split(/\s+/));

            instance.area.appendChild(div);

            return div;
        }

        /**
         * Creates the screen palette UI with a label and a refresh button.
         * @param {number} bottom - The vertical position for the elements.
         * @param {number} gridWidthColors - The width of the color grid in pixels.
         * @param {number} gridHeightColors - The height of the color grid in pixels.
         * @param {number} gridWidthAvatars - The width of the avatar grid in pixels.
         * @param {number} gridHeightAvatars - The height of the avatar grid in pixels.
         */
        gateCreateScreenPalette(bottom, gridWidthColors, gridHeightColors, gridWidthAvatars, gridHeightAvatars) {
            const instance = this;

            // Create and configure the label
            const label = instance.createDOMElement('label', {
                parent: instance.area,
                styles: {
                    position: 'absolute',
                    font: 'FontAwesome',
                    fontSize: `${instance.fontSize}px`,
                    width: '0px',
                    whiteSpace: 'nowrap',
                    color: instance.getContrastingColor(instance.colorBackground),
                    top: `${bottom}px`,
                    left: '0px',
                },
                attributes: {
                    innerHTML: instance.getStringM('js_palette'),
                },
            });

            // Create the refresh button
            const btn = instance.createImageButton(
                instance.area,
                'mmogame-gate-palette',
                label.scrollWidth + instance.padding, bottom,
                instance.iconSize, instance.fontSize,
                'assets/refresh.svg',
                false, 'refresh'
            );

            // Add event listener to refresh button
            this.addEventListenerRefresh(btn, bottom, gridWidthColors, gridHeightColors,
                gridWidthAvatars, gridHeightAvatars, true, false);
        }

        gateCreateButtonSubmit = (maxWidth, bottom2) => {
            this.btnSubmit = this.createImageButton(this.area, 'mmogame-gate-submit',
                (maxWidth - this.iconSize) / 2, bottom2, 0, this.iconSize,
                'assets/submit.svg', false, 'submit');
            this.btnSubmit.style.visibility = 'hidden';
            this.btnSubmit.addEventListener("click", () => {
                if (this.edtCode !== undefined) {
                    this.user = this.edtCode.value;
                }
                this.gatePlayGame(true, this.edtNickname.value, this.paletteid, this.avatarid);
            });
        };


        /**
         * Adds an event listener to refresh colors and avatars.
         *
         * @param {HTMLElement} btn - The button to attach the event listener to.
         * @param {number} bottom - The Y-coordinate offset for grid positioning.
         * @param {number} gridWidthColors - Width of the color grid.
         * @param {number} gridHeightColors - Height of the color grid.
         * @param {number} gridWidthAvatars - Width of the avatar grid.
         * @param {number} gridHeightAvatars - Height of the avatar grid.
         * @param {boolean} updateColors - Callback to update colors.
         * @param {boolean} updateAvatars - Callback to update avatars.
         */
        addEventListenerRefresh(btn, bottom, gridWidthColors, gridHeightColors, gridWidthAvatars, gridHeightAvatars,
                                updateColors, updateAvatars) {
            btn.addEventListener("click", () => {
                const elements = Array.from(this.area.getElementsByClassName("mmogame_color"));
                elements.forEach(element => element.remove());

                this.gateSendGetColorsAvatars(0, bottom, gridWidthColors, gridHeightColors,
                    0, bottom + gridHeightColors + this.fontSize + this.padding, gridWidthAvatars, gridHeightAvatars,
                    updateColors, updateAvatars);
            });
        }

        /**
         * Creates the main game area.
         */
        createArea() {
            const instance = this;

            if (instance.area) {
                instance.body.removeChild(instance.area);
            }

            instance.area = this.createDiv(
                instance.body,
                'mmogame-area',
                instance.padding,
                instance.areaTop,
                instance.areaWidth,
                instance.areaHeight
            );
        }

        /**
         * Creates a modal dialog.
         * @param {string} classnames - The CSS class for the modal.
         * @param {string} title - The title of the modal.
         * @param {string} content - The content of the modal.
         * @returns {HTMLElement} - The modal element.
         */
        createModal(classnames, title, content) {
            const modal = this.createDOMElement('div', {
                parent: this.body,
                classnames: `${classnames} modal`,
                styles: {
                    position: 'fixed',
                    top: '50%',
                    left: '50%',
                    transform: 'translate(-50%, -50%)',
                    backgroundColor: '#fff',
                    boxShadow: '0 4px 8px rgba(0, 0, 0, 0.2)',
                    padding: '20px',
                    zIndex: 1000,
                },
            });

            const header = this.createDOMElement('div', {
                parent: modal,
                classnames: `${classnames}-header`,
                styles: {
                    fontWeight: 'bold',
                    marginBottom: '10px',
                },
            });
            header.innerText = title;

            const body = this.createDOMElement('div', {
                parent: modal,
                classnames: `${classnames}-body`,
            });
            body.innerHTML = content;

            const closeButton = this.createDOMElement('button', {
                parent: modal,
                classnames: `${classnames}-close`,
                styles: {
                    marginTop: '10px',
                    display: 'block',
                    marginLeft: 'auto',
                    marginRight: 'auto',
                },
                attributes: {type: 'button'},
            });
            closeButton.innerText = 'Close';

            closeButton.addEventListener('click', () => {
                this.body.removeChild(modal);
            });

            return modal;
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
            let top = instance.areaTop;
            let width = window.innerWidth - 2 * instance.padding;
            let height = window.innerHeight - instance.getCopyrightHeight() - instance.padding - top;

            instance.createDivMessageDo(classnames, left, top, width, height, message, height);

            instance.divMessage.style.top = (height - instance.divMessage.scrollHeight) / 2 + "px";
        }

        createButtonAvatar(prefixclassname, left, topNickName, widthNickName, heightNickName, topAvatar, widthAvatar, title) {
            const nickname = this.createDOMElement('div', {
                classname: `${prefixclassname}-nickname`,
                parent: this.body,
                styles: {
                    left: left,
                    top: topNickName,
                    width: widthNickName
                },
                attributes: {
                    title: title
                }
            });

            const avatar = this.createDOMElement('img', {
                classname: `${prefixclassname}-avatar`,
                parent: this.body,
                styles: {
                    left: left,
                    top: topAvatar,
                    width: widthAvatar,
                },
                attributes: {
                    title: title
                }
            });


            return {nickname: nickname, avatar: avatar};
        }


        createDivMessageStart(message) {
            const instance = this;

            if (instance.area !== undefined) {
                instance.body.removeChild(instance.area);
                instance.area = undefined;
            }

            let left = instance.padding;
            let top = instance.areaTop;
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

                div.style.color = instance.getContrastingColor(instance.colorDefinition);
                let top = instance.iconSize + 3 * instance.padding + height1;
                div.style.top = (top + instance.padding) + "px";
                div.style.height = (height - height1) + "px";
                instance.divMessageHelp = div;
                instance.body.appendChild(instance.divMessageHelp);

                instance.showHelpScreen(div, (width - 2 * instance.padding), (height - height1));
            }
        }

        updateButtonsAvatar(num, avatar, nickname) {
            if (avatar === undefined) {
                avatar = "";
            }
            if (nickname === undefined) {
                nickname = "";
            }

            if (avatar === "" && nickname === "") {
                this.buttonsAvatar[num].style.visibility = 'hidden';
                this.divNicknames[num].style.visibility = 'hidden';
                return;
            }

            if (this.nicknames[num] !== nickname || nickname === "") {
                this.nicknames[num] = nickname;
                let s = nickname;

                if (nickname.length === 0) {
                    s = avatar;
                    let pos = s.lastIndexOf("/");
                    if (pos >= 0) {
                        s = s.slice(pos + 1);
                    }
                    pos = s.lastIndexOf(".");
                    if (pos >= 0) {
                        s = s.slice(0, pos);
                    }
                    const filenameWithExt = avatar.split('/').pop(); // Extract the file name with its extension
                    s = filenameWithExt.split('.').slice(0, -1).join('.'); // Remove the extension from the file name
                }
                s = this.repairNickname(s);
                if (this.divNicknames[num] !== undefined && this.divNicknames[num].innerHTML !== s) {
                    this.divNicknames[num].innerHTML = s;
                    this.divNicknames[num].style.textAlign = "center";
                    this.divNicknames[num].style.color = this.getContrastingColor(this.colorsBackground);
                    this.autoResizeText(this.divNicknames[num], this.divNicknamesWidth[num], this.divNicknamesHeight[num], true,
                        0, 0, 1);
                }
            }

            if (avatar !== this.buttonsAvatarSrc[num]) {
                this.updateImageButton(this.buttonsAvatar[num], avatar !== "" ? "assets/avatars/" + avatar : "");
                this.buttonsAvatarSrc[num] = avatar;
            }

            this.buttonsAvatar[num].alt = this.divNicknames[num].innerHTML;

            this.buttonsAvatar[num].style.visibility = 'visible';
            this.divNicknames[num].style.visibility = 'visible';
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