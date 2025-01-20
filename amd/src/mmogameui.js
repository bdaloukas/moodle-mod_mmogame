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

        vertical;
        kindSound; // Type: Number (0 = on, 1 = off, 2 = speak)
        buttonSound;
        colorDefinition = 0;
        colorScore;

        // Other
        definition;
        buttonAvatarHeight;
        buttonAvatarTop;
        colorBackground = 0xFFFFFF;

        url;
        mmogameid;
        pin;
        user;

        constructor() {
            super();
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

        createButtonSound(left, top) {
            this.buttonSound = this.createButton(
                this.body,
                'mmogame-button-sound',
                left,
                top,
                this.iconSize,
                this.iconSize,
                this.getMuteFile(),
                this.getStringM('js_sound')
            );
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
            this.url = url;
            this.minFontSize *= 2;
            this.maxFontSize *= 2;

            // Saves parameters to class variables.
            this.mmogameid = mmogameid;
            this.pin = pin;
            this.kinduser = kinduser;
            this.user = user;
            this.gateComputeSizes();

            this.areaTop = this.padding;
            this.areaWidth = Math.round(window.innerWidth - 2 * this.padding);
            this.areaHeight = Math.round(window.innerHeight - this.areaTop) - this.padding;

            let instance = this;
            this.getOptions().then(function(options) {
                options.kindsound = options.kindsound || 0;
                options.nickname = options.nickname || '';
                options.avatarid = options.avatarid || 0;
                options.paletteid = options.paletteid || 0;
                instance.kindSound = [1, 2].includes(options.kindSound) ? options.kindSound : 0;

                const isReady =
                    options.nickname &&
                    options.avatarid !== 0 &&
                    options.paletteid !== 0;

                if (kinduser === 'moodle' && isReady) {
                    instance.gatePlayGame(false, options.nickname, options.paletteid, options.avatarid);
                    return;
                }

                if (kinduser === 'guid') {
                    options.userGUID = options.userGUID || '';

                    if (options.userGUID.length >= 10 && isReady) {
                        instance.user = options.userGUID;
                        instance.gatePlayGame(false, options.nickname, options.paletteid, options.avatarid);
                        return;
                    }
                }
                instance.gateCreateScreen();
            })
            .catch((error) => {
                this.showError('gateOpen', error);
            });
        }

        gatePlayGame(save, nickname, paletteid, avatarid) {
            if (this.kinduser === 'guid') {
                if (this.user === '') {
                    this.uuid4();
                }
            }

            if (!save) {
                this.nickname = nickname;
                this.paletteid = paletteid;
                this.avatarid = avatarid;
                this.callGetAttempt({nickname: nickname, colorpaletteid: paletteid, avatarid: avatarid});
                return;
            }

            let options = {nickname: nickname, avatarid: avatarid, paletteid: paletteid};

            let instance = this;
            this.setOptions(options)
                .then(function() {
                    instance.nickname = nickname;
                    instance.paletteid = paletteid;
                    instance.avatarid = avatarid;
                    instance.callGetAttempt();
                    return true;
                })
                .catch(error => {
                    instance.showError(error.message);
                    return false;
                });
        }

        gateCreateScreen() {
            this.vertical = window.innerWidth < window.innerHeight;
            this.createArea();

            if (this.vertical) {
                this.gateCreateScreenVertical();
            } else {
                this.gateCreateScreenHorizontal();
            }
        }

        gateCreateScreenVertical() {
            let maxHeight = this.areaHeight - 5 * this.padding - this.iconSize;
            let maxWidth = this.areaWidth;
            let instance = this;
            let size;

            const labels = [this.getStringM('js_name') + ": ", this.getStringM('js_code'), this.getStringM('js_palette')];
            this.fontSize = this.findbest(this.minFontSize, this.maxFontSize, function(fontSize) {
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
                let computedHeight = heightCode + 3 * size[1] + 8 * instance.padding + heightColors + heightAvatars;

                return computedHeight < maxHeight ? -1 : 1;
            });

            let gridWidthColors = maxWidth - this.padding;
            let gridWidthAvatars = maxWidth - this.padding;
            let gridHeightColors = (maxHeight - 4 * this.fontSize) * 2 / 5;
            let newHeight = Math.floor(gridHeightColors / instance.iconSize) * instance.iconSize;
            let newWidth = Math.floor(gridWidthColors / instance.iconSize) * instance.iconSize;
            let rest = gridHeightColors - newHeight;
            gridHeightColors = newHeight;
            let gridHeightAvatars = (maxHeight - 4 * this.fontSize + rest) * 3 / 5;

            // Creates the "Code" field.
            let bottom;
            if (this.kinduser !== 'guid' && this.kinduser !== 'moodle') {
                // A bottom = this.gateCreateCode(0, 0, maxWidth, this.fontSize, size[0]);
                bottom = this.gateCreateLabelEditVertical(0, 0, maxWidth, this.fontSize,
                    size[0], this.getStringM('js_code') + ": ",
                    'mmogame-gate-code-label', 'mmogame-gate-code') + 2 * this.padding;
                this.edtCode = this.edt;
                this.edtCode.addEventListener("keyup", function() {
                    instance.gateUpdateSubmit();
                });
            } else {
                bottom = 0;
            }

            bottom = this.gateCreateLabelEditVertical(0, bottom,
                newWidth - 2 * this.padding, this.fontSize, size[0],
                this.getStringM('js_name') + ": ",
                'mmogame-gate-name-label', 'mmogame-gate-name') + 2 * this.padding;
            this.edtNickname = this.edt;
            this.edtNickname.addEventListener("keyup", function() {
                instance.gateUpdateSubmit();
            });

            this.gateCreateScreenPalette(bottom, gridWidthColors, gridHeightColors,
                gridWidthAvatars, gridHeightAvatars);

            bottom += this.fontSize + this.padding;

            // Vertical
            this.gateSendGetColorsAvatars(0, bottom, gridWidthColors, gridHeightColors,
                0, bottom + gridHeightColors + this.fontSize + this.padding, gridWidthAvatars,
                gridHeightAvatars);

            let bottom2 = bottom + gridHeightColors + this.fontSize + this.padding + gridHeightAvatars;

            this.gateCreateButtonSubmit(maxWidth, bottom2);
        }

        gateCreateScreenHorizontal() {
            let maxHeight = this.areaHeight - 7 * this.padding - this.iconSize;
            let maxWidth = this.areaWidth;
            let instance = this;
            let size;

            const sName = this.getStringM('js_name') + ": ";
            let labels = [this.getStringM('js_code'), sName, this.getStringM('js_palette')];

            this.fontSize = this.findbest(this.minFontSize, this.maxFontSize,
                function(fontSize) {
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

            let gridWidthColors = maxWidth - this.padding;
            let gridWidthAvatars = maxWidth - this.padding;
            let gridHeightColors = (maxHeight - 4 * this.fontSize) * 2 / 5;
            let newHeight = Math.floor(gridHeightColors / instance.iconSize) * instance.iconSize;
            let newWidth = Math.floor(gridWidthColors / instance.iconSize) * instance.iconSize;
            let rest = gridHeightColors - newHeight;
            gridHeightColors = newHeight;
            let gridHeightAvatars = Math.floor((maxHeight - 4 * this.fontSize) * 3 / 5 + rest);

            // Creates the "Code" field.
            let bottom;
            if (this.kinduser !== 'guid' && this.kinduser !== 'moodle') {
                // B bottom = this.gateCreateCode(0, 0, maxWidth, this.fontSize, size[0]);
                bottom = this.gateCreateLabelEditVertical(0, 0, maxWidth, this.fontSize, size[0],
                    this.getStringM('js_code')) + 2 * this.padding;
                this.edtCode = this.edt;
                this.edtCode.addEventListener("keyup", function() {
                    instance.gateUpdateSubmit();
                });
            } else {
                bottom = 0;
            }

            // Creates the "nickname" field.
            let sizeLabel = this.gateComputeLabelSize(this.fontSize, [sName]);
            bottom = this.gateCreateLabelEditHorizontal(0, bottom,
                newWidth - 2 * this.padding, this.fontSize,
                sizeLabel[0], this.getStringM('js_name') + ": ",
                'mmogame-gate-name-label', 'mmogame-gate-name');

            this.edtNickname = this.edt;

            this.edtNickname.addEventListener("keyup", function() {
                instance.gateUpdateSubmit();
            });

            let label1 = document.createElement("label");
            label1.style.position = "absolute";
            label1.style.color = this.getContrastingColor(this.colorBackground);
            label1.innerHTML = this.getStringM('js_palette');
            label1.style.font = "FontAwesome";
            label1.style.fontSize = this.fontSize + "px";
            label1.style.width = "0px";
            label1.style.whiteSpace = "nowrap";
            this.area.appendChild(label1);

            // Button refresh color palettes
            let btn = this.createImageButton(this.area, 'mmogame-button-gate-refresh',
                label1.scrollWidth + this.padding, bottom, this.iconSize, this.fontSize,
                'assets/refresh.svg', false, 'refresh');
            this.addEventListenerRefresh(btn, bottom, gridWidthColors, gridHeightColors,
                gridWidthAvatars, gridHeightAvatars, true, false);

            label1.style.left = 0;
            label1.style.color = this.getContrastingColor(this.colorBackground);
            label1.style.top = bottom + "px";
            bottom += this.fontSize + this.padding;

            let label = document.createElement("label");
            label.style.position = "absolute";
            label.innerHTML = this.getStringM('js_avatars');
            label.style.font = "FontAwesome";
            label.style.fontSize = this.fontSize + "px";
            label.style.width = "0 px";
            label.style.whiteSpace = "nowrap";
            this.area.appendChild(label);

            // Button refresh avatars
            btn = this.createImageButton(this.area, 'mmogame-button-gate-refresh-avatars',
                label.scrollWidth + this.padding, bottom + gridHeightColors, this.iconSize,
                this.fontSize, 'assets/refresh.svg', false, 'refresh');
            btn.addEventListener("click",
                function() {
                    let elements = instance.area.getElementsByClassName("mmogame_avatar");

                    while (elements[0]) {
                        elements[0].parentNode.removeChild(elements[0]);
                    }

                    instance.gateSendGetColorsAvatars(0, bottom, gridWidthColors, gridHeightColors, 0,
                        bottom + gridHeightColors + instance.fontSize + instance.padding, gridWidthAvatars, gridHeightAvatars,
                        false, true);
                }
            );

            // Avatar
            label.style.left = "0 px";
            label.style.color = this.getContrastingColor(this.colorBackground);
            label.style.top = (bottom + gridHeightColors) + "px";

            // Horizontal
            this.gateSendGetColorsAvatars(0, bottom, gridWidthColors, gridHeightColors,
                0, bottom + gridHeightColors + this.fontSize + this.padding, gridWidthAvatars,
                gridHeightAvatars);

            let bottom2 = bottom + gridHeightColors + this.fontSize + this.padding + gridHeightAvatars;
            this.btnSubmit = this.createImageButton(this.area, 'mmogame-button-gate-submit',
                (maxWidth - this.iconSize) / 2, bottom2, 0, this.iconSize,
                'assets/submit.svg', false, 'submit');
            this.btnSubmit.style.visibility = 'hidden';
            this.btnSubmit.addEventListener("click",
                function() {
                    if (instance.edtCode !== undefined) {
                        instance.user = instance.edtCode.value;
                    }
                    instance.gatePlayGame(true, instance.edtNickname.value, instance.paletteid, instance.avatarid);
                }
            );
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

        gateCreateLabelEditVertical(left, top, width, fontSize, labelWidth, title, classnamesLabel, classnamesEdit) {
            const label = this.createLabel(this.area, classnamesLabel, left, top, labelWidth, fontSize, title);
            label.style.color = this.getContrastingColor(this.colorBackground);

            top += label.scrollHeight;

            this.edt = this.gateCreateInput(classnamesEdit, left, top, width, fontSize);

            return top + fontSize + this.padding;
        }

        gateShowAvatars(left, top, width, height, countX, avatarids, avatars) {
            if (!avatars || avatars.length === 0) {
                return; // Exit early if no avatars exist
            }

            this.avatar = undefined;
            const count = avatars.length;
            let leftOriginal = left;
            let w = Math.round(this.padding / 2) + "px";
            for (let i = 0; i < count; i++) {
                let avatarImagePath = 'assets/avatars/' + avatars[i];
                let btn = this.createCenterImageButton(
                    this.area,
                    left, top,
                    this.iconSize - this.padding, this.iconSize - this.padding,
                    'mmogame-avatar',
                    avatarImagePath
                );
                btn.classList.add("mmogame_avatar");
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
        }

        gateSendGetColorsAvatars(leftColors, topColors, gridWidthColors, gridHeightColors, leftAvatars, topAvatars,
                                 gridWidthAvatars, gridHeightAvatars, updateColors = true, updateAvatars = true) {

            let countXcolors = Math.floor(gridWidthColors / this.iconSize);
            let countYcolors = Math.floor(gridHeightColors / this.iconSize);

            let countXavatars = Math.floor(gridWidthAvatars / this.iconSize);
            let countYavatars = Math.floor((gridHeightAvatars + 2 * this.padding) / this.iconSize);

            if (!updateColors) {
                countXcolors = countXcolors = 0;
            }
            if (!updateAvatars) {
                countXavatars = countYavatars = 0;
            }

            let instance = this;
            require(['core/ajax'], function(Ajax) {
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
                getAssets[0].done(function({avatarids, avatars, colorpaletteids, colorpalettes}) {
                    if (updateColors) {
                        instance.gateShowColorPalettes(leftColors, topColors, gridWidthColors,
                            gridHeightColors, countXcolors, countYcolors, colorpaletteids, colorpalettes);
                    }
                    if (updateAvatars) {
                        instance.gateShowAvatars(leftAvatars, topAvatars, gridWidthAvatars, gridHeightAvatars, countXavatars,
                            avatarids, avatars);
                    }
                }).fail(function(error) {
                    return error;
                });
            });
        }

        gateShowColorPalettes(left, top, width, height, countX, countY, colorpaletteids, colorpalettes) {
            let i = 0; // Counter for color palettes
            const count = colorpalettes.length;
            this.canvasColor = undefined;
            const canvasSize = this.iconSize - this.padding * 3 / 2;
            const parsedPalettes = colorpalettes.map(palette =>
                palette.split(",").map(value => parseInt(value, 10) || 0)
            );
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
                    this.area.appendChild(canvas);

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
            this.area.classList.add("palette");
        }

        gateUpdateColorPalette(canvas, id) {
            if (this.canvasColor !== undefined) {
                this.canvasColor.style.borderStyle = "none";
            }
            this.canvasColor = canvas;
            canvas.style.borderStyle = "outset";
            let w = Math.round(this.padding / 2) + "px";
            canvas.style.borderLeftWidth = w;
            canvas.style.borderTopWidth = w;
            canvas.style.borderRightWidth = w;
            canvas.style.borderBottomWidth = w;

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
            const isCodeValid = this.edtCode?.value ? Number(this.edtCode.value) > 0 : true;
            const hasAvatar = this.avatarid !== undefined;
            const hasPalette = this.paletteid !== undefined;
            const hasNickname = this.edtNickname?.value?.length > 0;

            this.btnSubmit.style.visibility =
                isCodeValid && hasAvatar && hasPalette && hasNickname
                    ? 'visible'
                    : 'hidden';
        }

        gateComputeSizes() {
            this.computeSizes();
            this.iconSize = Math.round(0.8 * this.iconSize);
            this.padding = Math.round(0.8 * this.padding);
        }

        gateCreateLabelEditHorizontal(left, top, width, fontSize, labelWidth, title, classnamesLabel, classnamesEdit) {
            const label = this.createLabel(this.area, classnamesLabel, left, top, labelWidth, fontSize, title);
            label.style.color = this.getContrastingColor(this.colorBackground);

            let ret = top + Math.max(label.scrollHeight, fontSize) + this.padding;

            let leftEdit = (left + labelWidth + this.padding);
            this.edt = this.gateCreateInput(classnamesEdit, leftEdit, top, width - leftEdit - this.padding, fontSize);

            return ret;
        }

        gateCreateInput(classnames, left, top, width, fontSize) {
            const div = document.createElement("input");
            div.style.position = "absolute";
            div.style.width = width + "px";
            div.style.type = "text";
            div.style.fontSize = fontSize + "px";

            div.style.left = left + "px";
            div.style.top = top + "px";
            div.autofocus = true;

            div.classList.add(...classnames.split(/\s+/));

            this.area.appendChild(div);
            this.edt = div;

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
            // Create and configure the label
            const label = this.createDOMElement('label', {
                parent: this.area,
                styles: {
                    position: 'absolute',
                    font: 'FontAwesome',
                    fontSize: `${this.fontSize}px`,
                    width: '0px',
                    whiteSpace: 'nowrap',
                    color: this.getContrastingColor(this.colorBackground),
                    top: `${bottom}px`,
                    left: '0px',
                },
                attributes: {
                    innerHTML: this.getStringM('js_palette'),
                },
            });

            // Create the refresh button
            const btn = this.createImageButton(
                this.area,
                'mmogame-gate-palette',
                label.scrollWidth + this.padding, bottom,
                this.iconSize, this.fontSize,
                'assets/refresh.svg',
                false, 'refresh'
            );

            // Add event listener to refresh button
            this.addEventListenerRefresh(btn, bottom, gridWidthColors, gridHeightColors, gridWidthAvatars, gridHeightAvatars);
        }

        gateCreateButtonSubmit = (maxWidth, bottom2) => {
            this.btnSubmit = this.createImageButton(this.area, 'mmogame-gate-submit',
                (maxWidth - this.iconSize) / 2, bottom2, 0, this.iconSize,
                'assets/submit.svg', false, 'submit');
            this.btnSubmit.style.visibility = 'hidden';
            const instance = this;
            this.btnSubmit.addEventListener("click", function() {
                if (instance.edtCode !== undefined) {
                    instance.user = instance.edtCode.value;
                }
                instance.gatePlayGame(true, instance.edtNickname.value, instance.paletteid, instance.avatarid);
            });
        };


        addEventListenerRefresh(btn, bottom, gridWidthColors, gridHeightColors, gridWidthAvatars, gridHeightAvatars,
                                updateColors, updateAvatars) {
            const instance = this;
            btn.addEventListener("click",
                function() {
                    let elements = instance.area.getElementsByClassName("mmogame_color");

                    while (elements[0]) {
                        elements[0].parentNode.removeChild(elements[0]);
                    }

                    instance.gateSendGetColorsAvatars(0, bottom, gridWidthColors, gridHeightColors,
                        0, bottom + gridHeightColors + instance.fontSize + instance.padding, gridWidthAvatars, gridHeightAvatars,
                        updateColors, updateAvatars);
                }
            );
        }

        /**
         * Creates the main game area.
         */
        createArea() {
            if (this.area) {
                this.body.removeChild(this.area);
            }

            this.area = this.createDiv(
                this.body,
                'mmogame-area',
                this.padding,
                this.areaTop,
                this.areaWidth,
                this.areaHeight
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
            if (this.area !== undefined) {
                this.body.removeChild(this.area);
                this.area = undefined;
            }

            if (this.divMessageHelp !== undefined) {
                this.body.removeChild(this.divMessageHelp);
                this.divMessageHelp = undefined;
            }

            let left = this.padding;
            let top = this.areaTop;
            let width = window.innerWidth - 2 * this.padding;
            let height = window.innerHeight - this.getCopyrightHeight() - this.padding - top;

            this.createDivMessageDo(classnames, left, top, width, height, message, height);

            this.divMessage.style.top = (height - this.divMessage.scrollHeight) / 2 + "px";
        }


        createButtonsAvatar(num, left, widthNickName = 0, heightNickName = 0) {
            if (widthNickName === 0) {
                widthNickName = this.iconSize;
            }
            if (heightNickName === 0) {
                heightNickName = this.iconSize - this.buttonAvatarHeight;
            }
            if (this.buttonsAvatarLeft === undefined) {
                this.buttonsAvatarLeft = [];
            }
            this.buttonsAvatarLeft[num] = left;

            if (this.buttonsAvatarSrc === undefined) {
                this.buttonsAvatarSrc = [];
            }
            this.buttonsAvatarSrc[num] = "";

            if (this.nicknames === undefined) {
                this.nicknames = [];
            }
            this.nicknames[num] = "";

            if (this.buttonsAvatar === undefined) {
                this.buttonsAvatar = [];
            }
            this.buttonsAvatar[num] = this.createImageButton(this.body, 'mmogame-avatar' + num,
                left, this.avatarTop, this.iconSize, this.iconSize);
            if (num === 2 && this.avatarTop !== undefined) {
                this.buttonsAvatar[num].title = this.getStringM('js_opponent');
            }

            if (this.divNicknames === undefined) {
                this.divNicknames = [];
            }
            if (this.divNicknamesWidth === undefined) {
                this.divNicknamesWidth = [];
            }
            if (this.divNicknamesHeight === undefined) {
                this.divNicknamesHeight = [];
            }
            this.divNicknamesWidth[num] = widthNickName;
            this.divNicknamesHeight[num] = heightNickName;

            this.divNicknames[num] = this.createDiv(this.body, 'mmogame-gate-nickname',
                left, this.padding, widthNickName, heightNickName);
        }


        createDivMessageStart(message) {
            if (this.area !== undefined) {
                this.body.removeChild(this.area);
                this.area = undefined;
            }

            let left = this.padding;
            let top = this.areaTop;
            let width = window.innerWidth - 2 * this.padding;
            let height = window.innerHeight - this.getCopyrightHeight() - this.padding - top;

            let height1 = height / 8;

            this.createDivMessageDo('mmogame-message-start', left, top, width, height, message, height1);

            top += (height1 - this.divMessage.scrollHeight) / 2;
            this.divMessage.style.top = top + "px";

            if (this.divMessageHelp === undefined) {
                let div = document.createElement("div");
                div.style.position = "absolute";
                div.style.left = left + "px";
                div.style.textAlign = "left";
                div.style.width = (width - 2 * this.padding) + "px";
                div.style.paddingLeft = this.padding + "px";
                div.style.paddingRight = this.padding + "px";

                div.style.color = this.getContrastingColor(this.colorDefinition);
                let top = this.iconSize + 3 * this.padding + height1;
                div.style.top = (top + this.padding) + "px";
                div.style.height = (height - height1) + "px";
                this.divMessageHelp = div;
                this.body.appendChild(this.divMessageHelp);

                this.showHelpScreen(div, (width - 2 * this.padding), (height - height1));
            }
        }


        /**
         * Creates a score display element.
         * @param {string} classnames - The list of classes.
         * @param {number} left - The left position in pixels.
         * @param {number} top - The top position in pixels.
         * @param {number} width - The width of the score element in pixels.
         * @param {number} height - The height of the score element in pixels.
         * @param {number} num - Identifier for the score element.
         */
        createAddScore(classnames, left, top, width, height, num) {
            const div = this.createDiv(this.body, classnames, left, top, width, height);
            div.style.textAlign = "center";
            div.style.color = this.getContrastingColor(this.colorScore);
            div.title = this.getStringM('js_grade_last_question');
            if (num === 1) {
                this.labelAddScore = div;
            } else {
                this.labelAddScore2 = div;
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

        callGetAttempt(extraparams) {
            let instance = this;
            require(['core/ajax'], function(Ajax) {
                let params = {
                    mmogameid: instance.mmogameid,
                    kinduser: instance.kinduser,
                    user: instance.user,
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
                getAttempt[0].done(function(response) {
                    if (extraparams !== undefined && extraparams.colorpaletteid !== undefined) {
                        instance.openGame();
                        instance.colors = undefined;
                    }
                    instance.processGetAttempt(JSON.parse(response));
                }).fail(function(error) {
                    instance.createDivMessage('mmogame-error', error.message);
                    return error;
                });
            });
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
};
});