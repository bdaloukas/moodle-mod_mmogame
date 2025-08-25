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
define(['mod_mmogame/mmogame', ''], function(MmoGame) {
    return class MmoGameTypeSplit extends MmoGame {
        updateDelay = 200;
        countX;
        countY;
        countAll;
        screen;

        gateShowColorPalettes(parent, countY, colorpaletteids, colorpalettes) {
            const left = this.padding;
            const topButton = this.iconSize + 2 * this.padding + Math.round(this.fontSize);
            const restHeight = window.innerHeight - topButton - countY * (this.iconSize + this.padding);
            const top = topButton + Math.round(restHeight / 2);
            const countX = 1;
            let i = 0; // Counter for color palettes
            const count = colorpalettes.length;
            this.canvasColor = undefined;
            const canvasSize = this.iconSize - this.padding * 3 / 2;
            const parsedPalettes = colorpalettes.map(palette =>
                palette.split(",").map(value => parseInt(value, 10) || 0)
            );
            let acanvas = [];
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
                    canvas.style.left = `${left + ix * (this.iconSize + this.padding)}px`;
                    canvas.style.top = `${top + iy * (this.iconSize + this.padding)}px`;
                    canvas.width = canvasSize;
                    canvas.height = canvasSize;
                    canvas.style.cursor = 'pointer';
                    canvas.classList.add("mmogame_color");

                    acanvas.push(canvas);

                    // Append canvas to the area
                    fragment.appendChild(canvas);

                    // Render the color palette on the canvas
                    const palette = parsedPalettes[i];
                    this.showColorPalette(canvas, palette);
                    // Get the palette ID and attach a click event listener
                    let id = colorpaletteids[i];
                    canvas.addEventListener("click", () => {
                        this.gateUpdateColorPalette(canvas, id, palette);
                    });

                    i++;
                }
            }
            parent.appendChild(fragment);

            return acanvas;
        }

        gateUpdateColorPalette(canvas, id, palette) {
            if (this.canvasColor !== undefined) {
                this.canvasColor.style.borderStyle = "none";
            }
            this.canvasColor = canvas;
            let w = Math.round(this.padding) + "px";

            Object.assign(canvas.style, {
                borderStyle: "outset",
                borderLeftWidth: w,
                borderTopWidth: w,
                borderRightWidth: w,
                borderBottomWidth: w,
            });
            this.paletteid = id;
            this.palette = palette;
        }

        gateUpdateAvatar(split, avatar, id) {
            const sp = this.splits[split];
            if (sp.avatar !== undefined) {
                sp.avatar.style.borderStyle = "none";
            }
            sp.avatar = avatar;
            avatar.style.borderStyle = "outset";

            const w = Math.round(this.padding) + "px";

            avatar.style.borderLeftWidth = w;
            avatar.style.borderTopWidth = w;
            avatar.style.borderRightWidth = w;
            avatar.style.borderBottomWidth = w;

            sp.avatarid = id;
        }

        updateGamepads(timestamp) {
            const gamepads = navigator.getGamepads();

            if (this.gateInfo !== undefined) {
                this.gateInfo.textContent = Array.from(navigator.getGamepads()).filter(gp => gp !== null).length;
            }

            const n = this.splits !== undefined ? this.splits.length : 0;
            for (let i = 0; i < gamepads.length; i++) {
                if (!gamepads[i]) {
                    continue;
                }
                if (i < n) {
                    this.updateGamepad(timestamp, i, gamepads[i]);
                }
            }
            this.updateScreen();
        }

        computeSizes(offsetY) {
            const cIcons = 3;

            const maxIconWidth = Math.floor(window.innerWidth / (this.countX * cIcons + 1.2 + this.countX / 10.0));
            const maxIconHeight = Math.floor(window.innerHeight / (this.countY + 0.5));
            const bodyFontSize = parseFloat(getComputedStyle(document.documentElement).fontSize);

            this.iconSize = Math.min(maxIconWidth, maxIconHeight, 4 * bodyFontSize);
            this.padding = Math.round(this.iconSize / 10);
            this.iconSize -= this.padding;
            this.split = {
                offsetY: offsetY * this.iconSize,
                width: Math.round((window.innerWidth - this.iconSize - 3 * this.padding) / this.countX),
                height: Math.round((window.innerHeight - offsetY * this.iconSize) / this.countY - this.padding)
            };
            this.createArea(0, 0);

            this.countXavatars = Math.floor((this.split.width - this.padding) / (this.iconSize + this.padding));
            this.countYavatars = Math.floor((this.split.height - 2 * this.padding) / (this.iconSize + this.padding));
            this.countPalettes = Math.floor((window.innerHeight - 3 * this.padding) / (this.iconSize + this.padding)) - 1;
            const widthSplits = this.countX * (this.split.width + this.padding) + this.padding;
            const space = window.innerWidth - widthSplits - this.iconSize - 2 * this.padding;
            this.split.offsetX = Math.round(this.iconSize + this.padding + space / 2);

            const max = 66;
            if (this.countXavatars * this.countYavatars > max) {
                if (this.countXavatars < this.countYavatars) {
                    this.countYavatars = Math.floor(max / this.countXavatars);
                } else {
                    this.countXavatars = Math.floor(max / this.countYavatars);
                }
            }
        }

        gateCreateScreen() {
            this.cIcons = 5;
            this.gateCreateSidebar();
            this.gateShowColorPalettes(this.body, this.countPalettes, this.info.colorpaletteids, this.info.colorpalettes);
            this.splits = [];
            for (let iY = 0; iY < this.countY; iY++) {
                for (let iX = 0; iX < this.countX; iX++) {
                    if (this.splits.length >= this.countAll) {
                        break;
                    }
                    this.gateCreateScreenSplit(iX, iY);
                    this.gateCreateScreenSplitSelect();
                }
            }
        }

        gateCreateScreenSplit(iX, iY) {
            let parent = this.createDOMElement('div', {
                parent: this.area,
                classnames: 'mmogame-split',
                styles: {
                    position: 'absolute',
                    left: `${this.split.offsetX + iX * (this.split.width + this.padding)}px`,
                    top: `${iY * (this.split.height + this.padding) + this.padding}px`,
                    width: `${this.split.width}px`,
                    height: `${this.split.height}px`,
                    overflow: 'hidden',
                    border: '1px solid blue',
                }
            });

            const labelHeight = this.countX * this.countY > 1 ? Math.round(this.iconSize / 3) : 0;
            const sizeAvatars = this.countYavatars * (this.iconSize + this.padding);
            const restHeight = this.split.height - labelHeight - 2 * this.padding - sizeAvatars;
            let top = labelHeight + this.padding + Math.round(restHeight / 2);
            const topLabel = Math.round((top - labelHeight) / 2);
            let avatarbuttons = [];
            let pos = (iX + this.countX * iY) * this.countYavatars * this.countXavatars;

            for (let i = 0; i < this.countYavatars; i++) {
                for (let j = 0; j < this.countXavatars; j++) {
                    if (pos >= this.info.avatars.length) {
                        break;
                    }
                    const filepath = this.info.avatars[pos];
                    const filenameWithExt = filepath.split('/').pop();
                    const filename = filenameWithExt.split('.').slice(0, -1).join('.');

                    const btn = this.createDOMElement('img', {
                        parent: parent,
                        classnames: 'mmogame-button-help',
                        styles: {
                            position: 'absolute',
                            left: `${this.padding + j * (this.iconSize + this.padding)}px`,
                            top: `${top}px`,
                            width: `${this.iconSize}px`,
                            height: `${this.iconSize}px`,
                        },
                        attributes: {
                            src: 'assets/avatars/' + filepath,
                            alt: filename,
                            role: 'button',
                        },
                    });
                    const avatarid = this.info.avatarids[pos];
                    const posAvatar = pos;
                    const split = this.splits.length;
                    btn.addEventListener("click", () => {
                        this.splits[split].avatarpos = posAvatar;
                        this.gateUpdateAvatar(split, btn, avatarid);
                    });
                    avatarbuttons.push(btn);
                    pos++;
                }
                top += this.iconSize + this.padding;
            }

            let labelAvatar = this.createDOMElement('label', {
                parent: parent,
                classnames: 'mmogame-gate-avatar-label',
                styles: {
                    position: 'absolute',
                    font: 'FontAwesome',
                    fontSize: `${labelHeight}px`,
                    lineHeight: `${labelHeight}px`,
                    width: '0px',
                    height: `${labelHeight}px`,
                    whiteSpace: 'nowrap',
                    color: this.getContrastingColor(this.colorBackground),
                    top: `${topLabel}px`,
                    left: `${this.padding}px`,
                },
            });
            if (this.countX * this.countY > 1) {
                labelAvatar.innerHTML = (iX + this.countX * iY + 1);
            }

            this.splits.push(
                {
                    div: parent,
                    avatarpos: -1,
                    avatarid: null,
                    avatarbuttons: avatarbuttons,
                    labelAvatar: labelAvatar,
                    lastUpdateTime: 0,
                }
            );
        }
        gategetavatar(split, i) {
            return split * this.info.numavatars + i;
        }

        gateCreateScreenSplitSelect() {
            const split = this.splits.length - 1;
            const sp = this.splits[split];

            // Selects a unique avatar for each player.
            let id;
            let selected = 0;

            for (;;) {
                id = this.info.avatarids[this.gategetavatar(split, selected)];
                let found = false;
                for (let i = 0; i < split - 1; i++) {
                    const avatarpos = this.splits[i].avatarpos;
                    if (this.info.avatarids[this.gategetavatar(i, avatarpos)] === id) {
                        found = true;
                        break;
                    }
                }
                if (!found) {
                    break;
                }
                selected++;
            }
            sp.avatarpos = selected;
            this.gateUpdateAvatar(split, sp.avatarbuttons[sp.avatarpos], id);
        }

        updateScreen() {
            requestAnimationFrame((t) => this.updateGamepads(t));
        }

        /**
         * Creates a percentage-based score display using createDOMElement.
         *
         * @param {any} parent
         * @param {number} left - The left position in pixels.
         * @param {boolean} showRank - .
         * */
        createDivScorePercent(parent, left, showRank) {
            const colorText = this.getContrastingColor(this.colorBackground);
            // Create the main button container
            const divMain = this.createDOMElement('div', {
                parent: parent,
                classnames: 'mmogame-quiz-main',
                styles: {
                    position: 'absolute',
                    left: `${left}px`,
                    top: `${this.padding}px`,
                    width: `${this.iconSize}px`,
                    height: `${this.iconSize}px`,
                    border: "0px solid " + this.getColorHex(this.colorBackground),
                    boxShadow: "inset 0 0 0.125em rgba(255, 255, 255, 0.75)",
                    background: this.getColorHex(this.colorBackground),
                    color: colorText,
                    borderRadius: `${this.iconSize}px`,
                },
                attributes: {
                    disabled: true,
                    innerHTML: '',
                },
            });

            const cellSize = showRank ? Math.round(this.iconSize / 2) : this.iconSize;

            // Create the ranking grade label (line1)
            let lblRank;
            if (showRank) {
                lblRank = this.createDOMElement('div', {
                    parent: parent,
                    classnames: `mmogame-pwn-rank`,
                    styles: {
                        position: 'absolute',
                        left: `${left}px`,
                        width: `${this.iconSize}px`,
                        top: `${this.padding}px`,
                        height: `${cellSize}px`,
                        lineHeight: `${cellSize}px`,
                        textAlign: 'center',
                        color: colorText,
                    },
                    attributes: {
                        title: this.getStringM('js_ranking'),
                    },
                });
            }

            // Create the main score label (line2)
            const lblScore = this.createDOMElement('div', {
                parent: parent,
                classnames: `mmogame-score`,
                styles: {
                    position: 'absolute',
                    left: `${left}px`,
                    width: `${this.iconSize}px`,
                    top: `${showRank ? this.padding + cellSize : this.padding}px`,
                    height: `${cellSize}px`,
                    lineHeight: `${cellSize}px`,
                    textAlign: 'center',
                    color: colorText,
                },
                attributes: {
                    title: this.getStringM('js_grade'),
                },
            });

            return {divMain: divMain, lblRank: lblRank, lblScore: lblScore, cellSize: cellSize};
        }

        moveX(timestamp, split, num, direction, steps) {
            if (split >= this.splits.length) {
                return;
            }
            let sp = this.splits[split];
            if (sp.lastUpdateTime > 0 && timestamp - sp.lastUpdateTime < this.updateDelay) {
                return;
            }
            sp.lastUpdateTime = timestamp;

            if (split >= this.splits.length) {
                return;
            }
            this.usersecondjoystick = false;
            split = (this.usersecondjoystick ? 2 : 1) * split + (num - 1);

            const n = this.countXavatars * this.countYavatars;

            let pos = this.splits[split].avatarpos;
            let id;
            for (; ;) {
                pos = (pos + direction * steps + n) % n;
                id = this.info.avatarids[this.gategetavatar(split, pos)];
                let found = false;
                for (let i = 0; i < this.splits.length; i++) {
                    if (i === split) {
                        continue;
                    }
                    const newid = this.splits[i].avatarid;
                    if (newid === id) {
                        found = true;
                    }
                }
                if (found === false) {
                    this.splits[split].avatarpos = pos;
                    this.splits[split].avatarid = id;
                    break;
                }
            }
            this.gateUpdateAvatar(split, this.splits[split].avatarbuttons[pos], id);
        }

        moveY(timestamp, split, num, direction, steps) {
            this.moveX(timestamp, split, num, direction, steps * this.countXavatars);
        }

        updateGamepad(timestamp, split, gamepad) {
            const axes = gamepad.axes;
            // Using https://luser.github.io/gamepadtest/.
            const axisX1 = axes?.[0] ?? 0; // Left stick X
            const axisY1 = axes?.[1] ?? 0; // Left stick Y
            /* Const axisX2 = axes?.[5] ?? 0; // Right stick X
            const axisY2 = axes?.[2] ?? 0; // Right stick Y
*/
            if (axisX1 < -0.5) {
                this.moveX(timestamp, split, 1, -1, 1);
            }
            if (axisX1 > 0.5) {
                this.moveX(timestamp, split, 1, 1, 1);
            }
            if (axisY1 < -0.5) {
                this.moveY(timestamp, split, 1, -1, 1);
            }
            if (axisY1 > 0.5) {
                this.moveY(timestamp, split, 1, 1, 1);
            }
            /*
                            // Second joystick.
                            if (this.usesecondjoystick) {
                                if (axisX2 < -0.5) {
                                    this.moveX(timestamp, i, 2, -1, 1);
                                }
                                if (axisX2 > 0.5) {
                                    this.moveX(timestamp, i, 2, 1, 1);
                                }
                                if (axisY2 < -0.5) {
                                    this.moveY(timestamp, i, 2, -1, 1);
                                }
                                if (axisY2 > 0.5) {
                                    this.moveY(timestamp, i, 2, 1, 1);
                                }
                            }
            */
        }

        gateCreateSidebar() {
            const btn = this.createDOMElement('img', {
                parent: this.body,
                classnames: 'mmogame-button-run',
                styles: {
                    position: 'absolute',
                    left: `${this.padding}px`,
                    top: `${this.padding}px`,
                    width: `${this.iconSize}px`,
                    height: `${this.iconSize}px`,
                },
                attributes: {
                    src: 'assets/start.svg',
                    alt: 'start',
                    role: 'button',
                },
            });
            btn.addEventListener("click", () => {
                this.play();
            });

            this.gateInfo = this.createDOMElement('div', {
                parent: this.body,
                classnames: 'mmogame-split-info',
                styles: {
                    position: 'absolute',
                    left: `0`,
                    top: `${this.iconSize + 2 * this.padding}px`,
                    width: `${this.iconSize}px`,
                    height: `${this.iconSize}px`,
                    textAlign: 'right',
                    color: this.getContrastingColor(this.colorBackground),
                },
            });

            const sel = this.createDOMElement('select', {
                parent: this.body,
                classnames: 'mmogame-select-split',
                styles: {
                    position: 'absolute',
                    left: `${this.padding}px`,
                    top: `${this.iconSize + 2 * this.padding}px`,
                    textAlign: 'left',
                }
            });
            for (let i = 1; i <= 8; i++) {
                const option = document.createElement("option");
                option.value = i;
                option.text = i;
                if (i - this.countAll === 0) {
                    option.selected = true;
                }
                sel.appendChild(option);
            }
            sel.addEventListener("change", () => {
                this.countAll = sel.value;
                this.countY = Math.round(Math.sqrt(this.countAll));
                this.countX = Math.ceil(this.countAll / this.countY);

                while (this.body.firstChild) {
                    this.body.removeChild(this.body.firstChild);
                }
                this.area = undefined;
                this.computeSizes(0);
                this.gateSendGetAssets();
            });
        }

        gateSendGetAssets() {
            require(['core/ajax'], (Ajax) => {
                // Defining the parameters to be passed to the service
                let params = {
                    mmogameid: this.mmogameid,
                    kinduser: this.kinduser,
                    user: this.user,
                    countsplit: this.countAll,
                    countpalettes: this.countPalettes,
                    countavatars: this.countXavatars * this.countYavatars,
                };
                // Calling the service through the Moodle AJAX API
                let getAssets = Ajax.call([{
                    methodname: 'mod_mmogame_get_assets_split',
                    args: params
                }]);
                // Handling the response
                getAssets[0].done(({avatarids, avatars, colorpaletteids, colorpalettes, numavatars}) => {
                    this.info = {
                        avatarids: avatarids,
                        avatars: avatars,
                        colorpaletteids: colorpaletteids,
                        colorpalettes: colorpalettes,
                        numavatars: numavatars,
                    };
                    this.gateCreateScreen();
                }).fail((error) => {
                    this.showError('gateSendGetAssets', error);
                    return error;
                });
            });
        }

        async gateOpen(mmogameid, pin, kinduser, user) {
            this.mmogameid = mmogameid;
            this.pin = pin;
            this.kinduser = kinduser;
            this.screen = 0;

            if (this.kinduser === 'guid') {
                const option = await this.getOption('guid' + mmogameid);
                if (option === null) {
                    this.user = crypto.randomUUID();
                    this.setOption('guid' + mmogameid, {value: this.user});
                } else {
                    this.user = option.value;
                }
            } else {
                this.kinduser = user;
            }
            this.gateSendGetAssets();
        }

    };
});
