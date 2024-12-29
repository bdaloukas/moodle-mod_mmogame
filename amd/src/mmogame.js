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
define([], function() {
    return class mmoGame {
    kindSound = 0;
    state = 0;
    body;
    minFontSize;
    maxFontSize;
    fontSize;
    avatarTop;
    colorsBackground;
    buttonAvatarTop;
    buttonAvatarHeight;
    colorScore;
    colorScore2;
    definition;
    colorDefinition;
    /**
     * Basic class for games.
     *
     * @module mmogame
     * @copyright 2024 Vasilis Daloukas
     * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
     */
    constructor() {
        this.body = document.getElementsByTagName("body")[0];
        this.kindSound = window.localStorage.getItem('kindSound');
        if (isNaN(this.kindSound)) {
            this.kindSound = 0;
        }
        if (this.kindSound !== 0 && this.kindSound !== 1 && this.kindSound !== 2) {
            this.kindSound = 0;
        }

        let size = parseFloat(window.getComputedStyle(document.documentElement).getPropertyValue('font-size'));
        this.minFontSize = size;
        this.maxFontSize = 2 * size;
        this.fontSize = size;
    }

    hasHelp() {
        return false;
    }

    removeBodyChilds() {
        this.removeDivMessage();

        while (this.body.firstChild) {
            this.body.removeChild(this.body.lastChild);
        }
        this.area = undefined;
    }

    openGame(url, id, pin, auserid, kinduser, callOnAfterOpenGame) {
        this.removeBodyChilds();
        this.area = undefined;

        this.game = [];

        this.url = url;
        this.mmogameid = id;
        this.pin = pin;
        this.kinduser = kinduser;
        if (this.kinduser === undefined || this.kinduser === "") {
            this.kinduser = "usercode";
        }
        this.auserid = auserid;
        if (this.auserid === 0 && this.kinduser === 'guid') {
            this.auserid = this.getUserGUID();
        }

        this.computeSizes();
        if (!callOnAfterOpenGame) {
            return;
        }
        if (this.kinduser === undefined) {
            this.onAfterOpenGame();
        } else {
            this.onAfterOpenGame();
        }
    }

    createImageButton(parent, left, top, width, height, classname, filename, wrap, alt) {
        let button = document.createElement("img");
        if (alt !== undefined && alt !== '') {
            button.alt = alt;
        }

        button.classList.add("mmogame_imgbutton");
        button.tabIndex = 0;
        button.setAttribute("role", "button");
        button.style.position = "absolute";
        button.style.left = left + "px";
        button.style.top = top + "px";
        button.draggable = false;

        if (width !== 0) {
            button.style.width = width + "px";
        }
        if (height !== 0) {
            button.style.height = height + "px";
        }
        button.style.fontSize = height + "px";
        if (filename !== undefined && filename !== '') {
            button.src = filename;
        }
        parent.appendChild(button);

        return button;
    }

    createImage(parent, left, top, width, height, filename) {
        let button = document.createElement("img");

        button.tabIndex = 0;
        button.style.position = "absolute";
        button.style.left = left + "px";
        button.style.top = top + "px";
        button.draggable = false;

        if (width !== 0) {
            button.style.width = width + "px";
        }
        if (height !== 0) {
            button.style.height = height + "px";
        }
        button.style.fontSize = height + "px";
        if (filename !== undefined && filename !== '') {
            button.src = filename;
        }
        parent.appendChild(button);

        return button;
    }

    createCenterImageButton(parent, left, top, width, height, classname, filename) {
        let button = document.createElement("img");
        button.classList.add("mmogame_imgbutton");
        button.style.position = "absolute";
        button.draggable = false;

        const img = new Image();
        img.onload = function() {
            if (this.width === 0 || this.height === 0) {
                this.width = this.height = 1;
            }

            if (this.width > 0 && this.height > 0) {
                let mul = Math.min(width / this.width, height / this.height);
                let w = Math.round(this.width * mul);
                let h = Math.round(this.height * mul);

                button.style.width = w + "px";
                button.style.height = h + "px";
                button.style.left = (left + width / 2 - w / 2) + "px";
                button.style.top = (top + height / 2 - h / 2) + "px";

                button.src = filename;
                button.innerHTML = filename;
                button.style.fontSize = height + "px";
            }
        };
        if (filename !== undefined && filename !== "") {
            img.style.left = left + "px";
            img.style.top = top + "px";
            img.style.visibility = 'hidden';
            img.src = filename;
        }
        button.tabIndex = 0;
        parent.appendChild(button);

        return button;
    }

    updateImageButton(button, left, top, width, height, filename) {
        button.src = filename;
    }

    createDiv(parent, left, top, width, height) {
        let div = document.createElement("div");
        div.style.position = "absolute";
        div.style.left = left + "px";
        div.style.top = top + "px";
        div.style.width = width + "px";
        div.style.height = height + "px";

        parent.appendChild(div);

        return div;
    }

    createDivColor(parent, left, top, width, height, color) {
        let div = this.createDiv(parent, left, top, width, height);
        div.style.background = this.getColorHex(color);

        return div;
    }

    getMuteFile() {
        if (this.kindSound === 0) {
            return 'assets/sound-on-flat.png';
        } else if (this.kindSound === 1) {
            return 'assets/sound-off-flat.png';
        } else if (this.kindSound === 2) {
            return 'assets/speak.svg';
        } else {
            return 'assets/sound-on-flat.png';
        }
    }

    playAudio(audio) {
        if (this.kindSound !== 0 && audio !== null) {
            if (audio.networkState === 1) {
                audio.play();
            }
        }
    }

    autoResizeText(item, width, height, wrap, minFontSize, maxFontSize, minRatio) {
        let minHeight = 0.9 * height;
        let low = Math.max(1, minFontSize);
        width = Math.round(width);
        height = Math.round(height);
        let up = maxFontSize === 0 || maxFontSize === undefined ? Math.min(width, height) : maxFontSize;

        let fitSize = low;
        let fitHeight = 0;
        let newHeight = 0;
        let newWidth = 0;

        for (let i = 0; i <= 10; i++) {
            let el = document.createElement("div");
            el.style.left = 0;
            el.style.top = 0;
            el.style.width = width + "px";
            el.style.height = 0;
            el.visibility = "visible";
            if (!wrap) {
                el.style.whiteSpace = "nowrap";
            }
            el.innerHTML = item.innerHTML;
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
                if (newHeight > minHeight) {
                    break;
                }
            }
        }
        item.style.fontSize = fitSize + "px";

        if (newWidth > width || newHeight > height) {
            this.autoResizeTextBr(item);
            newWidth = item.scrollWidth;
            newHeight = item.scrollHeight;
            this.autoResizeTextImage(item, newWidth > width ? newWidth - width : 0, newHeight > height ? newHeight - height : 0,
                minRatio);
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
        el.innerHTML = item.innerHTML;
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

    getUserGUID() {
        let guid = window.localStorage.getItem('UserGUID');
        if (guid === null || guid === '') {
            guid = this.uuid4();
            window.localStorage.setItem("UserGUID", guid);
        }

        return guid;
    }

    pad(num, size) {
        let s = num + "";
        while (s.length < size) {
            s = "0" + s;
        }
        return s;
    }

    uuid4() {
        const uuid = new Array(36);

        for (let i = 0; i < 36; i++) {
            uuid[i] = Math.floor(Math.random() * 16);
        }
        uuid[8] = uuid[13] = uuid[18] = uuid[23] = '-';

        return uuid.map((x) => x.toString(16)).join('');
    }

    computeSizes() {
        if (this.cIcons < 5 || this.cIcons === undefined) {
            this.cIcons = 5;
        }
        this.iconSize = Math.min(window.innerWidth / this.cIcons, window.innerHeight / 5);

        this.iconSize = Math.round(this.iconSize - this.iconSize / 10 / this.cIcons);

        this.padding = Math.round(this.iconSize / 10);
        this.iconSize -= this.padding;
    }

    getIconSize() {
        return this.iconSize;
    }

    getCopyrightHeight() {
        return Math.round(this.getIconSize() / 3);
    }

    getPadding() {
        return this.padding;
    }

    getColorHex(x) {
        return '#' + ('0000' + x.toString(16).toUpperCase()).slice(-6);
    }

    getContrast(x) {
        let r = Math.floor(x / 0x1000000) % 256, // Red.
            g = Math.floor(x / 0x10000) % 256, // Green.
            b = Math.floor(x / 0x100) % 256; // Blue.

        return ((r * 299) + (g * 587) + (b * 114)) / 1000;
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

    getColorContrast(x) {
        return (this.getContrast(x) >= 128) ? '#000000' : '#ffffff';
    }

    repairColors(colors) {

        this.colors = colors;
        let instance = this;
        this.colors.sort(function(a, b) {
            return instance.getContrast(a) - instance.getContrast(b);
        });

        this.colorBackground = this.colors[0];
        this.body.style.background = this.getColorHex(this.colorBackground);

        return this.colors;
    }
    repairHTML(s, mapFiles, mapFilesWidth, mapFilesHeight) {
        if (s === undefined) {
            return '';
        }
        while (s.slice(0, 4) === '<br>') {
            s = s.slice(4).trim();
        }
        for (; ;) {
            let pos = s.indexOf("@@GUID@@");
            if (pos < 0) {
                break;
            }
            let pos2 = s.indexOf("\"", pos);
            if (pos2 > 0) {
                let s2 = s.slice(pos2 + 1);
                let posw = s2.indexOf("width=");
                if (posw !== 0) {
                    let posw2 = s2.slice(posw + 1, posw + 2) === "\""
                        ? s2.indexOf("\"", posw + 2)
                        : s2.indexOf(" ", posw + 2);
                    if (posw2 !== 0) {
                        s2 = s2.slice(0, posw) + s2.slice(posw2 + 1);
                    }
                }
                posw = s2.indexOf("height=");
                if (posw !== 0) {
                    let posw2 = s2.slice(posw + 1, posw + 2) === "\""
                        ? s2.indexOf("\"", posw + 2)
                        : s2.indexOf(" ", posw + 2);
                    if (posw2 !== 0) {
                        s2 = s2.slice(0, posw) + s2.slice(posw2 + 1);
                    }
                }

                let key = s.substring(pos + 8, pos2);
                s2 = " width=\"" + mapFilesWidth.get(key) + "\" " + s2;
                s2 = " height=\"" + mapFilesHeight.get(key) + "\" " + s2;

                s = s.substring(0, pos) + "data:image/png;base64, " + mapFiles.get(key) + "\"" + s2;
            }
        }

        return s;
    }

    repairP(s) {
        if (s === undefined) {
            return "";
        }

        // Remove <p> tags and replace </p> with <br />
        let s2 = s.replace(/<p[^>]*>/g, '').replace(/<\/p>/g, '<br/>').trim();

        // Remove <br> or <br/> at the end of the string
        s2 = s2.replace(/<br\s*\/?>$/g, '');

        // Remove the <ol><br></ol> sequence if it exists
        let pos = s2.indexOf('<ol><br></ol>');
        if (pos >= 0) {
            s2 = s2.slice(0, pos) + s2.slice(pos + 13);
        }

        return s2;
    }

    createDivScorePercent(left, top, num) {
        let button = document.createElement("button");
        button.style.position = "absolute";
        button.classList.add("mmogame_button");
        button.style.left = left + "px";
        button.style.top = top + "px";
        button.style.width = this.iconSize + "px";
        button.style.height = this.iconSize + "px";
        button.style.lineHeight = this.iconSize + "px";
        button.style.textAlign = "center";
        button.style.border = "0px solid " + this.getColorHex(0xFFFFFF);
        button.style.boxShadow = "inset 0 0 0.125em rgba(255, 255, 255, 0.75)";
        if (num === 1) {
            button.style.background = this.getColorHex(this.colorScore);
            button.style.color = this.getColorContrast(this.colorScore);
            button.title = this.getStringM('js_grade');
        } else {
            button.style.background = this.getColorHex(this.colorScore2);
            button.style.color = this.getColorContrast(this.colorScore2);
        }

        this.body.appendChild(button);

        button.innerHTML = '';
        button.disabled = true;

        let div = this.createDiv(this.body, left, top + this.iconSize / 4, this.iconSize / 2, this.iconSize / 2);
        div.style.lineHeight = (this.iconSize / 2) + "px";
        div.style.textAlign = "center";
        div.style.color = this.getColorContrast(this.colorScore);
        if (num === 1) {
            this.labelScore = div;
            div.title = this.getStringM('js_grade');
        } else {
            this.labelScore2 = div;
            div.title = this.getStringM('js_grade_opponent');
        }

        let h = this.iconSize / 3;
        div = this.createDiv(this.body, left, top, this.iconSize / 2, h);
        div.style.textAlign = "center";
        div.style.color = this.getColorContrast(this.colorScore);
        div.title = this.getStringM('js_ranking_grade');
        if (num === 1) {
            this.labelScoreRank = div;
        } else {
            this.labelScoreRank2 = div;
        }

        div = this.createDiv(this.body, left, top + this.iconSize - h, this.iconSize / 2, h);
        div.style.textAlign = "center";
        div.style.color = this.getColorContrast(this.colorScore);
        div.title = this.getStringM('js_grade_last_question');
        button.disabled = true;
        if (num === 1) {
            this.labelAddScore = div;
        } else {
            this.labelAddScore2 = div;
        }

        let label = num === 1 ? this.labelScoreRank : this.labelScoreRank2;

        div = this.createDiv(this.body, left + this.iconSize / 2, top, this.iconSize / 2, h);
        div.style.textAlign = "center";
        div.style.fontName = label.style.fontName;
        div.style.fontSize = h + "px";
        div.style.lineHeight = h + "px";
        div.style.color = label.style.color;
        div.title = this.getStringM('js_ranking_percent');
        if (num === 1) {
            this.labelScoreRankB = div;
        }

        label = num === 1 ? this.labelAddScore : this.labelAddScore2;
        div = this.createDiv(this.body, left + this.iconSize / 2, parseFloat(this.labelScore.style.top), this.iconSize / 2,
            this.iconSize / 2);
        div.style.textAlign = "center";
        div.style.lineHeight = Math.round(this.iconSize / 2) + "px";
        div.style.fontName = label.style.fontName;
        div.style.fontSize = label.style.fontSize;
        div.style.fontWeight = 'bold';
        div.style.color = label.style.color;
        div.title = this.getStringM('js_percent');
        this.autoResizeText(div, this.iconSize / 2, this.iconSize / 2, false);
        if (num === 1) {
            this.labelScoreB = div;
        }
    }

    createDivTimer(left, top, sizeIcon) {
        let div = document.createElement("div");
        div.style.position = "absolute";
        div.style.left = left + "px";
        div.style.top = top + "px";
        div.style.width = sizeIcon + "px";
        div.style.height = sizeIcon + "px";
        div.style.whiteSpace = "nowrap";
        div.style.lineHeight = sizeIcon + "px";
        div.style.textAlign = "center";
        div.style.background = this.getColorHex(this.colorBackground); // "#234025"
        div.style.color = this.getColorContrast(this.colorBackground); // "#DCBFDA"
        div.name = "timer";
        this.body.appendChild(div);
        this.labelTimer = div;

        div.innerHTML = '23:59';
        this.autoResizeText(div, sizeIcon, sizeIcon, false, 0, 0, 1);
        div.innerHTML = '';
        div.title = this.getStringM('js_question_time');
    }

    createButtonSound(left, top) {
        this.buttonSound = this.createImageButton(this.body, left, top, this.iconSize, this.iconSize, "mmogame_button_red",
            this.getMuteFile());
        this.buttonSound.alt = this.getStringM('js_sound');
        let instance = this;
        this.buttonSound.addEventListener("click", function() {
            instance.onClickSound(instance.buttonSound);
        });

        this.buttonSound.title = this.getStringM('js_sound');
    }

    onClickSound(btn) {
        window.title = btn.src;
        this.kindSound = (this.kindSound + 1) % 2;
        window.localStorage.setItem("kindSound", toString(this.kindSound));
        btn.src = this.getMuteFile();
    }

    createButtonHelp(left, top) {
        this.buttonHelp = this.createImageButton(this.body, left, top, this.iconSize, this.iconSize, "", 'assets/help.svg');
        this.buttonHelp.alt = this.getStringM('js_help');
    }

    readJsonFiles(json) {
        this.mapFiles = new Map();
        this.mapFilesWidth = new Map();
        this.mapFilesHeight = new Map();
        this.mapFiles.set(json.fileg1, json.filec1);
        this.mapFilesWidth.set(json.fileg1, json.width1);
        this.mapFilesHeight.set(json.fileg1, json.height1);
    }

    findbest(low, up, fn) {
        if (low < 1) {
            low = 1;
        }

        let prevSize;
        let fitSize = low;
        let testSize;
        for (let i = 0; i <= 10; i++) {
            prevSize = low;
            testSize = (low + up) / 2;

            let cmp = fn(testSize);
            if (cmp <= 0) {
                fitSize = testSize;
                low = testSize;
            } else {
                up = testSize;
            }
            if (Math.abs((testSize - prevSize) / testSize) < 0.01) {
                break;
            }
        }

        return fitSize;
    }

    createDefinition(left, top, width, onlyMetrics, fontSize) {

        width -= 2 * this.padding;
        let div = document.createElement("div");

        div.style.position = "absolute";
        div.style.width = width + "px";

        div.style.fontSize = fontSize + "px";
        div.innerHTML = this.repairHTML(this.definition, this.mapFiles, this.mapFilesWidth, this.mapFilesHeight);
        div.style.textAlign = "left";

        if (onlyMetrics) {
            this.body.appendChild(div);
            let ret = [div.scrollWidth - 1, div.scrollHeight];
            this.body.removeChild(div);
            return ret;
        }

        div.style.background = this.getColorHex(this.colorDefinition);
        div.style.color = this.getColorContrast(this.colorDefinition);
        div.style.left = left + "px";
        div.style.top = top + "px";
        div.style.paddingLeft = this.padding + "px";
        div.style.paddingRight = this.padding + "px";
        div.id = "definition";

        this.area.appendChild(div);
        let height = div.scrollHeight + this.padding;
        div.style.height = height + "px";

        this.definitionHeight = height;
        this.divDefinition = div;

        return [div.scrollWidth - 1, div.scrollHeight];
    }

    updateLabelTimer() {
        if (this.labelTimer === undefined) {
            return;
        }
        if (this.timeclose === 0 || this.timestart === 0) {
            this.labelTimer.innerHTML = '';
            return;
        }

        let dif = Math.round(this.timeclose - ((new Date()).getTime()) / 1000);
        if (dif <= 0) {
            dif = 0;
            this.sendTimeout();
        }
        const minutes = Math.floor(Math.abs(dif) / 60);
        const seconds = Math.abs(dif) % 60;
        const prefix = dif < 0 ? "-" : "";
        this.labelTimer.innerHTML = `${prefix}${minutes}:${seconds.toString().padStart(2, '0')}`;

        if (dif === 0) {
            return;
        }

        let instance = this;
        setTimeout(function() {
            instance.updateLabelTimer();
        }, 500);
    }

    createDivMessage(message) {
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

        if (this.divMessageBackground === undefined) {
            let div = document.createElement("div");
            div.style.position = "absolute";
            div.style.left = left + "px";
            div.style.top = top + "px";
            div.style.width = width + "px";
            div.style.height = height + "px";
            div.style.background = this.getColorHex(this.colorDefinition);
            this.divMessageBackground = div;
            this.body.appendChild(this.divMessageBackground);
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
            div.style.color = this.getColorContrast(this.colorDefinition);
            this.divMessage = div;
        }
        this.divMessage.innerHTML = message;
        this.body.appendChild(this.divMessage);
        this.divMessage.style.top = (height - this.divMessage.scrollHeight) / 2 + "px";

        this.autoResizeText(this.divMessage, width, height, false, this.minFontSize, this.maxFontSize, 0.5);
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

        if (this.divMessageBackground === undefined) {
            let div = document.createElement("div");
            div.style.position = "absolute";
            div.style.left = left + "px";
            div.style.top = top + "px";
            div.style.width = width + "px";
            div.style.height = height + "px";
            div.style.background = this.getColorHex(this.colorDefinition);
            this.divMessageBackground = div;
            this.body.appendChild(this.divMessageBackground);
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
            div.style.color = this.getColorContrast(this.colorDefinition);
            this.divMessage = div;
        }
        this.divMessage.innerHTML = message;
        this.body.appendChild(this.divMessage);

        this.autoResizeText(this.divMessage, width, height1, false, this.minFontSize, this.maxFontSize, 0.5);
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

            div.style.color = this.getColorContrast(this.colorDefinition);
            let top = this.iconSize + 3 * this.padding + height1;
            div.style.top = (top + this.padding) + "px";
            div.style.height = (height - height1) + "px";
            this.divMessageHelp = div;
            this.body.appendChild(this.divMessageHelp);

            this.showHelpScreen(div, (width - 2 * this.padding), (height - height1));
        }
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

    showScore(json) {
        let s = json.sumscore === undefined ? '' : '<b>' + json.sumscore + '</b>';
        if (this.labelScore.innerHTML !== s) {
            this.labelScore.innerHTML = s;
            let w = this.iconSize - 2 * this.padding;
            this.autoResizeText(this.labelScore, w, this.iconSize / 2, false, 0, 0, 1);
        }

        if (this.labelScoreRank.innerHTML !== json.rank) {
            this.labelScoreRank.innerHTML = json.rank !== undefined ? json.rank : '';
            this.autoResizeText(this.labelScoreRank, this.iconSize, this.iconSize / 3, true, 0, 0, 1);
        }

        if (json.name !== undefined) {
            window.document.title = (json.usercode !== undefined ? json.usercode : "") + " " + json.name;
        }

        s = json.addscore === undefined ? '' : json.addscore;
        if (this.labelAddScore.innerHTML !== s) {
            this.labelAddScore.innerHTML = s;
            this.autoResizeText(this.labelAddScore, this.iconSize - 2 * this.padding, this.iconSize / 3, false, 0, 0, 1);
        }

        if (this.labelScoreRankB.innerHTML !== json.completedrank) {
            this.labelScoreRankB.innerHTML = json.completedrank !== undefined ? json.completedrank : '';
            this.autoResizeText(this.labelScoreRankB, 0.9 * this.iconSize / 2, this.iconSize / 3, true, 0, 0, 1);
        }

        s = json.percentcompleted !== undefined ? Math.round(100 * json.percentcompleted) + '%' : '';
        if (this.labelScoreB.innerHTML !== s) {
            this.labelScoreB.innerHTML = s;
            this.autoResizeText(this.labelScoreB, 0.8 * this.iconSize / 2, this.iconSize / 3, true, 0, 0, 1);
        }
    }

    createDivName(left, top, labelWidth, inpWidth, label, value) {
        let div = this.createDiv(this.area, left, top, labelWidth, this.iconSize);
        div.innerHTML = label + ": ";
        div.style.whiteSpace = "nowrap";
        div.style.textAlign = "right";
        div.style.color = this.getColorContrast(this.colorBackground);
        this.autoResizeText(div, labelWidth, this.areaHeight, false, this.minFontSize, this.maxFontSize, 1);

        let divInp = document.createElement("input");
        divInp.style.position = "absolute";
        divInp.style.width = inpWidth + "px";
        divInp.style.type = "text";
        divInp.style.fontSize = div.style.fontSize;
        divInp.value = value;
        divInp.style.left = (left + labelWidth + this.padding) + "px";
        divInp.style.top = top + "px";
        divInp.autofocus = true;
        divInp.style.background = this.getColorHex(this.colorBackground);
        divInp.style.color = this.getColorContrast(this.colorBackground);
        this.area.appendChild(divInp);
        divInp.autofocus = true;
        let instance = this;
        divInp.addEventListener("keydown", function() {
            if (event.key === "Enter") {
                instance.sendSetAvatar(divInp.value, -1);
            }
        });

        return divInp;
    }

    createCheckbox(parent, left, top, width, height, value) {
        let checkbox = document.createElement('input');
        checkbox.type = "checkbox";
        checkbox.value = value;
        checkbox.style.position = "absolute";
        checkbox.style.left = left + "px";
        checkbox.style.top = top + "px";
        checkbox.style.width = width + "px";
        checkbox.style.height = height + "px";

        parent.appendChild(checkbox);

        return checkbox;
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

    sendSetAvatar(nickname, avatarid) {
        let xmlhttp = new XMLHttpRequest();
        let instance = this;
        xmlhttp.onreadystatechange = function() {
            if (this.readyState === 4 && this.status === 200) {
                let json = JSON.parse(this.responseText);
                instance.updateButtonsAvatar(1, json.avatar);
                instance.sendGetAttempt();
            }
        };

        xmlhttp.open("POST", this.url, true);
        xmlhttp.setRequestHeader("Content-Type", "application/json");
        let data = JSON.stringify({
            "command": "setavatar", "mmogameid": this.mmogameid, "pin": this.pin,
            'kinduser': this.kinduser, "user": this.auserid, 'avatarid': avatarid, 'nickname': nickname
        });
        xmlhttp.send(data);
    }

    sendSetColorPalette(colorpaletteid) {
        let xmlhttp = new XMLHttpRequest();
        let instance = this;
        xmlhttp.onreadystatechange = function() {
            if (this.readyState === 4 && this.status === 200) {
                instance.colors = undefined;
                instance.openGame(instance.url, instance.mmogameid, instance.pin, instance.auserid, instance.kinduser);
            }
        };

        xmlhttp.open("POST", this.url, true);
        xmlhttp.setRequestHeader("Content-Type", "application/json");
        let data = JSON.stringify({
            "command": "setcolorpalette", "mmogameid": this.mmogameid, "pin": this.pin,
            'kinduser': this.kinduser, "user": this.auserid, "id": colorpaletteid
        });
        xmlhttp.send(data);
    }

    computeDifClock(json) {
        if (json.time !== undefined) {
            this.difClock = ((new Date()).getTime() - json.time) / 1000;
        }

        this.computeTimeStartClose(json);
    }

    computeTimeStartClose(json) {
        if (json.timestart !== undefined) {
            this.timestart = parseInt(json.timestart) !== 0 ? parseInt(json.timestart) + this.difClock : 0;
            this.timeclose = parseInt(json.timeclose) !== 0 ? parseInt(json.timeclose) + this.difClock : 0;
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
        this.buttonsAvatar[num] = this.createImageButton(this.body, left, this.avatarTop, this.iconSize, this.iconSize, "", "");
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

        this.divNicknames[num] = this.createDiv(this.body, left, this.padding, widthNickName, heightNickName);
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
                    s = s.substr(pos + 1);
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
                this.divNicknames[num].style.color = this.getColorContrast(this.colorsBackground);
                this.autoResizeText(this.divNicknames[num], this.divNicknamesWidth[num], this.divNicknamesHeight[num], true,
                    0, 0, 1);
            }
        }

        if (avatar !== this.buttonsAvatarSrc[num]) {
            this.updateImageButton(this.buttonsAvatar[num], this.buttonsAvatarLeft[num], this.buttonAvatarTop, this.iconSize,
                this.buttonAvatarHeight, avatar !== "" ? "assets/avatars/" + avatar : "");
            this.buttonsAvatarSrc[num] = avatar;
        }

        this.buttonsAvatar[num].alt = this.divNicknames[num].innerHTML;

        this.buttonsAvatar[num].style.visibility = 'visible';
        this.divNicknames[num].style.visibility = 'visible';
    }

    // Gate
    gateOpen(mmogameid, pin, kinduser, auserid) {
        this.minFontSize *= 2;
        this.maxFontSize *= 2;

        this.mmogameid = mmogameid;
        this.pin = pin;
        this.auserid = auserid;
        this.kinduser = kinduser;
        this.gateComputeSizes();
        this.areaTop = this.padding;

        this.areaWidth = Math.round(window.innerWidth - 2 * this.padding);
        this.areaHeight = Math.round(window.innerHeight - this.areaTop) - this.padding;

        switch (kinduser) {
            case 'moodle':
                if (localStorage.getItem("nickname") !== null && localStorage.getItem("avatarid") !== null
                    && localStorage.getItem("paletteid") !== null) {
                    let avatarid = parseInt(localStorage.getItem("avatarid"));
                    let paletteid = parseInt(localStorage.getItem("paletteid"));
                    this.gatePlaygame(auserid, localStorage.getItem("nickname"), paletteid, avatarid);
                    return;
                }
                break;
            case 'guid':
                if (localStorage.getItem("auserid") !== null && localStorage.getItem("nickname") !== null
                    && localStorage.getItem("avatarid") !== null && localStorage.getItem("paletteid") !== null) {
                    let avatarid = parseInt(localStorage.getItem("avatarid"));
                    let paletteid = parseInt(localStorage.getItem("paletteid"));
                    this.gatePlaygame(localStorage.getItem("auserid"), localStorage.getItem("nickname"), paletteid, avatarid);
                    return;
                }
                break;
        }

        this.gateCreateScreen();
    }

    gatePlaygame(auserid, nickname, paletteid, avatarid) {
        if (auserid === 0 && this.kinduser === 'guid') {
            auserid = this.getUserGUID();
        }
        localStorage.setItem("auserid", auserid);
        localStorage.setItem("nickname", nickname);
        localStorage.setItem("paletteid", paletteid);
        localStorage.setItem("avatarid", avatarid);

        let xmlhttp = new XMLHttpRequest();
        let instance = this;
        xmlhttp.onreadystatechange = function() {
            if (this.readyState === 4 && this.status === 200) {
                instance.gateOnServerGetAttempt(JSON.parse(this.responseText), auserid);
            }
        };

        xmlhttp.open("POST", this.url, true);
        xmlhttp.setRequestHeader("Content-Type", "application/json");
        let d = {
            "command": "getattempt", "mmogameid": this.mmogameid, "pin": this.pin, "kinduser": this.kinduser,
            "user": auserid, "nickname": nickname, "paletteid": paletteid, "avatarid": avatarid
        };
        if (this.helpurl === undefined) {
            d.helpurl = 1;
        }
        let data = JSON.stringify(d);
        xmlhttp.send(data);
    }

    gateOnServerGetAttempt(json, auserid) {
        if (json.errorcode !== undefined) {
            return;
        }

        this.openGame(this.url, this.mmogameid, this.pin, auserid, this.kinduser, false);
        this.onServerGetAttempt(json);
    }

    gateCreateScreen() {
        this.vertical = window.innerWidth < window.innerHeight;

        if (this.area !== undefined) {
            this.body.removeChild(this.area);
            this.area = undefined;
        }
        this.removeDivMessage();

        this.area = this.createDiv(this.body, this.padding, this.areaTop, this.areaWidth, this.areaHeight);

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

        let bottom;
        if (this.kinduser !== 'guid' && this.kinduser !== 'moodle') {
            bottom = this.gateCreateCode(0, 0, maxWidth, this.fontSize, size[0]);
            this.edtCode = this.edt;
            this.edtCode.addEventListener("keyup", function() {
                instance.gateUpdateSubmit();
            });
        } else {
            bottom = 0;
        }

        bottom = this.gateCreateLabelEditVertical(0, bottom, newWidth - 2 * this.padding, this.fontSize, size[0],
            this.getStringM('js_name') + ": ") + 2 * this.padding;
        this.edtNickname = this.edt;
        this.edtNickname.addEventListener("keyup", function() {
            instance.gateUpdateSubmit();
        });

        let label1 = document.createElement("label");
        label1.style.position = "absolute";
        label1.innerHTML = this.getStringM('js_palette');
        label1.style.font = "FontAwesome";
        label1.style.fontSize = this.fontSize + "px";
        label1.style.width = "0px";
        label1.style.whiteSpace = "nowrap";
        this.area.appendChild(label1);

        let btn = this.createImageButton(this.area, label1.scrollWidth + this.padding, bottom, this.iconSize, this.fontSize,
            '', 'assets/refresh.svg', false, 'refresh');
        btn.addEventListener("click",
            function() {
                let elements = instance.area.getElementsByClassName("mmogame_color");

                while (elements[0]) {
                    elements[0].parentNode.removeChild(elements[0]);
                }

                instance.gateSendGetColorsAvatarsVertical(0, bottom, gridWidthColors, gridHeightColors,
                    0, bottom + gridHeightColors + instance.fontSize + instance.padding, gridWidthAvatars, gridHeightAvatars,
                    true, false);
            }
        );
        label1.style.left = 0;
        label1.style.color = this.getColorContrast(this.colorBackground);
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
        btn = this.createImageButton(this.area, label.scrollWidth + this.padding, bottom + gridHeightColors, this.iconSize,
            this.fontSize, '', 'assets/refresh.svg', false, 'refresh');
        btn.addEventListener("click",
            function() {
                let elements = instance.area.getElementsByClassName("mmogame_avatar");

                while (elements[0]) {
                    elements[0].parentNode.removeChild(elements[0]);
                }

                instance.gateSendGetColorsAvatarsVertical(0, bottom, gridWidthColors, gridHeightColors,
                    0, bottom + gridHeightColors + instance.fontSize + instance.padding, gridWidthAvatars, gridHeightAvatars,
                    false, true);
            }
        );

        label.style.left = "0 px";
        label.style.color = this.getColorContrast(this.colorBackground);
        label.style.top = (bottom + gridHeightColors) + "px";

        // Vertical
        this.gateSendGetColorsAvatarsVertical(0, bottom, gridWidthColors, gridHeightColors,
            0, bottom + gridHeightColors + this.fontSize + this.padding, gridWidthAvatars,
            gridHeightAvatars);

        let bottom2 = bottom + gridHeightColors + this.fontSize + this.padding + gridHeightAvatars;
        this.btnSubmit = this.createImageButton(this.area, (maxWidth - this.iconSize) / 2, bottom2, 0,
            this.iconSize, "", 'assets/submit.svg', false, 'submit');
        this.btnSubmit.style.visibility = 'hidden';
        this.btnSubmit.addEventListener("click", function() {
            instance.gatePlaygame(instance.edtCode === undefined ? instance.auserid : instance.edtCode.value,
                instance.edtNickname.value, instance.paletteid, instance.avatarid);
        });
    }

    gateCreateScreenHorizontal() {
        let maxHeight = this.areaHeight - 7 * this.padding - this.iconSize;
        let maxWidth = this.areaWidth;
        let instance = this;
        let size;

        const sName = this.getStringM('js_name') + ": ";
        let labels = [this.getStringM('js_code'), sName, this.getStringM('js_palette')];

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

        let bottom;
        if (this.kinduser !== 'guid' && this.kinduser !== 'moodle') {
            bottom = this.gateCreateCode(0, 0, maxWidth, this.fontSize, size[0]);
            this.edtCode = this.edt;
            this.edtCode.addEventListener("keyup", function() {
                instance.gateUpdateSubmit();
            });
        } else {
            bottom = 0;
        }
        let sizeLabel = this.gateComputeLabelSize(this.fontSize, [sName]);
        bottom = this.gateCreateLabelEdit(0, bottom, newWidth - 2 * this.padding,
            this.fontSize, sizeLabel[0], this.getStringM('js_name') + ": ");

        this.edtNickname = this.edt;
        this.edtNickname.addEventListener("keyup", function() {
            instance.gateUpdateSubmit();
        });

        let label1 = document.createElement("label");
        label1.style.position = "absolute";
        label1.innerHTML = this.getStringM('js_palette');
        label1.style.font = "FontAwesome";
        label1.style.fontSize = this.fontSize + "px";
        label1.style.width = "0px";
        label1.style.whiteSpace = "nowrap";
        this.area.appendChild(label1);

        let btn = this.createImageButton(this.area, label1.scrollWidth + this.padding, bottom, this.iconSize, this.fontSize,
            '', 'assets/refresh.svg', false, 'refresh');
        btn.addEventListener("click",
            function() {
                let elements = instance.area.getElementsByClassName("mmogame_color");

                while (elements[0]) {
                    elements[0].parentNode.removeChild(elements[0]);
                }

                instance.gateSendGetColorsAvatarsVertical(0, bottom, gridWidthColors, gridHeightColors, 0,
                    bottom + gridHeightColors + instance.fontSize + instance.padding, gridWidthAvatars, gridHeightAvatars,
                    true, false);
            }
        );
        label1.style.left = 0;
        label1.style.color = this.getColorContrast(this.colorBackground);
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
        btn = this.createImageButton(this.area, label.scrollWidth + this.padding, bottom + gridHeightColors, this.iconSize,
            this.fontSize, '', 'assets/refresh.svg', false, 'refresh');
        btn.addEventListener("click",
            function() {
                let elements = instance.area.getElementsByClassName("mmogame_avatar");

                while (elements[0]) {
                    elements[0].parentNode.removeChild(elements[0]);
                }

                instance.gateSendGetColorsAvatarsVertical(0, bottom, gridWidthColors, gridHeightColors, 0,
                    bottom + gridHeightColors + instance.fontSize + instance.padding, gridWidthAvatars, gridHeightAvatars,
                    false, true);
            }
        );

        // Avatar
        label.style.left = "0 px";
        label.style.color = this.getColorContrast(this.colorBackground);
        label.style.top = (bottom + gridHeightColors) + "px";

        // Horizontal
        this.gateSendGetColorsAvatarsVertical(0, bottom, gridWidthColors, gridHeightColors,
            0, bottom + gridHeightColors + this.fontSize + this.padding, gridWidthAvatars,
            gridHeightAvatars);

        let bottom2 = bottom + gridHeightColors + this.fontSize + this.padding + gridHeightAvatars;
        this.btnSubmit = this.createImageButton(this.area, (maxWidth - this.iconSize) / 2, bottom2, 0,
            this.iconSize, "", 'assets/submit.svg', false, 'submit');
        this.btnSubmit.style.visibility = 'hidden';
        this.btnSubmit.addEventListener("click",
            function() {
                instance.gatePlaygame(instance.edtCode === undefined ? instance.auserid : instance.edtCode.value,
                    instance.edtNickname.value, instance.paletteid, instance.avatarid);
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

    gateCreateLabelEdit(left, top, width, fontSize, labelWidth, title) {
        let label = document.createElement("label");
        label.style.position = "absolute";

        label.innerHTML = title;

        label.style.font = "FontAwesome";
        label.style.fontSize = fontSize + "px";

        this.area.appendChild(label);

        label.style.position = "absolute";
        label.style.left = left + "px";
        label.style.top = top + "px";
        label.style.width = labelWidth + "px";
        label.style.align = "left";
        label.style.color = this.getColorContrast(this.colorBackground);

        let ret = top + Math.max(label.scrollHeight, fontSize) + this.padding;

        let leftEdit = (left + labelWidth + this.padding);
        const div = document.createElement("input");
        this.divShortAnswer = div;
        div.style.position = "absolute";
        div.style.width = (width - leftEdit - this.padding) + "px";
        div.style.type = "text";
        div.style.fontSize = fontSize + "px";

        div.style.left = leftEdit + "px";
        div.style.top = top + "px";
        div.autofocus = true;

        this.area.appendChild(div);
        this.edt = div;

        return ret;
    }

    gateCreateLabelEditVertical(left, top, width, fontSize, labelWidth, title) {
        const label = document.createElement("label");
        label.style.position = "absolute";

        label.innerHTML = title;

        label.style.font = "FontAwesome";
        label.style.fontSize = fontSize + "px";

        this.area.appendChild(label);

        label.style.position = "absolute";
        label.style.left = left + "px";
        label.style.top = top + "px";
        label.style.width = labelWidth + "px";
        label.style.align = "left";
        label.style.color = this.getColorContrast(this.colorBackground);

        top += label.scrollHeight;

        let leftEdit = left + 'px';
        let div = document.createElement("input");
        this.divShortAnswer = div;
        div.style.position = "absolute";
        div.style.width = width + "px";
        div.style.type = "text";
        div.style.fontSize = fontSize + "px";

        div.style.left = leftEdit + "px";
        div.style.top = top + "px";
        div.autofocus = true;

        this.area.appendChild(div);
        this.edt = div;

        return top + fontSize + this.padding;
    }

    gateCreateCode(left, top, width, fontSize, labelWidth) {
        return this.gateCreateLabelEdit(left, top, width, fontSize, labelWidth, this.getStringM('js_code'));
    }

    gateShowAvatars(response, left, top, width, height, countX) {
        if (!response.avatars || response.avatars.length === 0) {
            return; // Exit early if no avatars exist
        }

        this.avatar = undefined;
        const count = response.avatars.length;
        let leftOriginal = left;
        let w = Math.round(this.padding / 2) + "px";
        for (let i = 0; i < count; i++) {
            let avatarImagePath = 'assets/avatars/' + response.avatars[i];
            let btn = this.createCenterImageButton(
                this.area,
                left, top,
                this.iconSize - this.padding, this.iconSize - this.padding,
                "",
                avatarImagePath
            );
            btn.classList.add("mmogame_avatar");
            let id = response.avatarids[i];
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

    gateSendGetColorsAvatarsVertical(leftColors, topColors, gridWidthColors, gridHeightColors, leftAvatars, topAvatars,
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
                user: instance.auserid,
                avatars: countXavatars * countYavatars,
                colorpalettes: countXcolors * countYcolors,
            };

            // Calling the service through the Moodle AJAX API
            let getAssets = Ajax.call([{
                methodname: 'mod_mmogame_get_assets',
                args: params
            }]);

            // Handling the response
            getAssets[0].done(function(response) {
                if (updateColors) {
                    instance.gateShowColorPalettes(response, leftColors, topColors, gridWidthColors, gridHeightColors,
                        countXcolors, countYcolors);
                }
                if (updateAvatars) {
                    instance.gateShowAvatars(response, leftAvatars, topAvatars, gridWidthAvatars, gridHeightAvatars, countXavatars);
                }
            }).fail(function(error) {
                return error;
            });
        });
    }

    gateShowColorPalettes(response, left, top, width, height, countX, countY) {
        let i = 0; // Counter for color palettes
        const count = response.colorpalettes.length;
        this.canvasColor = undefined;
        const canvasSize = this.iconSize - this.padding * 3 / 2;
        const parsedPalettes = response.colorpalettes.map(palette =>
            palette.split(",").map(value => parseInt(value, 10) || 0)
        );
        for (let iy = 0; iy < countY; iy++) {
            for (let ix = 0; ix < countX; ix++) {
                // Check if we exceed available palettes or encounter invalid data
                if (i >= count || !parsedPalettes[i] || !response.colorpaletteids[i]) {
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
                let id = response.colorpaletteids[i];
                canvas.addEventListener("click", ()=> {
                    this.gateUpdateColorPalete(canvas, id);
                });

                i++;
            }
        }
        this.area.classList.add("palete");
    }

    gateUpdateColorPalete(canvas, id) {
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

    gateUpdateSubmit() {
        let hasCode = this.edtCode === undefined ? true : parseInt(this.edtCode.value) > 0;
        let visible = this.avatarid !== undefined && this.paletteid !== undefined && hasCode && this.edtNickname.value.length > 0;

        this.btnSubmit.style.visibility = visible ? 'visible' : 'hidden';
    }
    gateComputeSizes() {
        this.computeSizes();
        this.iconSize = Math.round(0.8 * this.iconSize);
        this.padding = Math.round(0.8 * this.padding);
    }
    getStringM(name) {
        return M.util.get_string(name, 'mmogame');
        }
    };
});