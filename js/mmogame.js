"use strict";

class mmogame {
    constructor() {
        this.blockServer = 0;
        this.kindSound = 0;
        this.state = 0;
        this.body = document.getElementsByTagName("body")[0];
        this.kindSound = window.localStorage.getItem('kindSound');
        if (isNaN(this.kindSound)) {
            this.kindSound = 0;
        }
        if (this.kindSound != 0 && this.kindSound != 1 && this.kindSound != 2) {
            this.kindSound = 0;
        }

        let size = parseFloat(window.getComputedStyle(document.documentElement).getPropertyValue('font-size'))
        this.minFontSize = size;
        this.maxFontSize = 2 * size;
        this.fontSize = size;
    }
    
    hasHelp() {
        return false;
    }

    createTextButton(parent, left, top, width, height, classname) {
        var button = document.createElement("button");
        button.classList.add("mmogame_button");
        if (classname != '') {
           button.classList.add(classname);
        }
        button.style.left = left + "px";
        button.style.top = top + "px";
        button.style.width = width + "px";
        button.style.height = height + "px";
        button.style.textAlign="center";
        button.style.border = "0px solid " + this.getColorHex(0xFFFFFF);
        button.style.boxShadow = "inset 0 0 0.125em rgba(255, 255, 255, 0.75)";

        parent.appendChild(button);

        return button;
    }

    isquestion_shortanswer() {
        return this.qtype == 'shortanswer';
    }

    setKindUser(kinduser) {
        this.kinduser = kinduser;
    }

    reopenGame() {
        sessionStorage.removeItem("auserid");
        this.openGame(this.url, this.mmogameid, this.pin, this.auserid, this.kinduser);
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
        this.buttonAvatarSrc = undefined;
        this.buttonAvatar = undefined;
        this.divNickname = undefined;

        this.game = [];

        this.url = url;
        this.mmogameid = id;
        this.pin = pin;
        this.kinduser = kinduser;
        if (this.kinduser == undefined || this.kinduser == "") {
            this.kinduser = "usercode";
        }
        this.auserid = auserid
        if (this.auserid == 0 && this.kinduser == 'guid') {
            this.auserid = this.getUserGUID();
        }

        this.computeSizes();
        if (!callOnAfterOpenGame) {
            return;
        }
        if (this.kinduser == undefined) {
            this.onAfterOpenGame();
        } else {
            this.onAfterOpenGame();
        }
    }

    createImageButton(parent, left, top, width, height, classname, filename, wrap, alt) {
        var button = document.createElement("img");
        if (alt != undefined && alt != '') {
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
        if (height != 0) {
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
        var button = document.createElement("img");

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

    createSVG(parent, left, top, width, height, alt, filename) {
        var button = document.createElement("img");
        button.src = filename;
        button.alt = alt;
        button.style.position = "absolute";
        button.style.left = left + "px";
        button.style.top = top + "px";
        
        if (width !== 0) {
            button.style.width = width + "px";
        }
        if (height !== 0) {
            button.style.height = height + "px";
        }
        button.innerHTML = filename;
        button.style.fontSize = height + "px";

        parent.appendChild(button);

        return button;
    }

    createCenterImageButton(parent, left, top, width, height, classname, filename, wrap) {
        var button = document.createElement("img");
        button.classList.add("mmogame_imgbutton");
        button.style.position = "absolute";
        button.draggable = false;

        let instance = this;
        const img = new Image();
        img.onload = function() {
            if (this.width == 0 || this.height == 0) {
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
        }
        if (filename != undefined && filename != "") {
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
    
    updateCenterImageButton(button, left, top, width, height, filename) {

        let instance = this;
        let img = new Image();

        if (filename != undefined && filename != "") {
            img.style.left = left + "px";
            img.style.top = top + "px";
            img.style.visibility = 'hidden';
            img.src = filename;
        }
        img.onload = function() {
            if (this.width == 0 || this.height == 0) {
                this.width = this.height = 1;
            }

            if (this.width > 0 && this.height > 0) {
                let mul = Math.min(width / this.width, height / this.height);
                let w = Math.round(this.width * mul);
                let h = Math.round(this.height * mul);
                
                // Converts to png for fast.
                var canvas = document.createElement('canvas');
                canvas.style.width = w;
                canvas.style.height = h;
                var ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0, img.width, img.height, 0, 0, w, h);
                let image = canvas.toDataURL("image/png");

                button.style.left = (left + width / 2 - w / 2) + "px";
                button.style.top = (top + height / 2 - h / 2) + "px";

                button.src = image;
            }
        }
    }

    createDiv(parent, left, top, width, height) {
        var div = document.createElement("div");
        div.style.position = "absolute";
        div.style.left = left + "px";
        div.style.top = top + "px";
        div.style.width = width + "px";
        div.style.height = height + "px";

        parent.appendChild(div);

        return div;
    }

    createDivColor(parent, left, top, width, height, color) {
        var div = this.createDiv(parent, left, top, width, height);
        div.style.background = this.getColorHex(color);

        return div;
    }

    getMuteFile() {
        if (this.kindSound == 0) {
            return 'assets/sound-on-flat.png';
        } else if (this.kindSound == 1) {
            return 'assets/sound-off-flat.png';
        } else if (this.kindSound == 2) {
            return 'assets/speak.svg';
        }
    }

    playAudio(audio) {
        if (this.kindSound != 0 && audio != null) {
            if (audio.networkState == 1) {
                audio.play();
            }
        }
    }

    autoResizeText(item, width, height, wrap, minFontSize, maxFontSize, minRatio) {
        var minHeight = 0.9 * height ;
        var low = Math.max(1, minFontSize);
        width = Math.round(width);
        height = Math.round(height);
        var up = maxFontSize == 0 || maxFontSize == undefined ? Math.min(width, height) : maxFontSize;

        var fitSize = low;
        var fitHeight = 0;
        let instance = this;

        for (var i=0; i <= 10; i++) {
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

            var fontSize = (low + up) / 2;

            el.style.fontSize = fontSize + "px";
            var newHeight = el.scrollHeight;
            var newWidth = el.scrollWidth - 1;

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
            this.autoResizeText_image(item, newWidth > width ? newWidth - width : 0, newHeight > height ? newHeight - height : 0,
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
        while (s.substr(0, 4) == '<br>') {
            s = s.substr(4);
            change = true;
        }
        let pos1 = s.indexOf('<br>');
        for (;;) {
            let pos = s.indexOf('<br>', pos1 + 4);
            if (pos < 0) {
                break;
            }
            let s2 = s.substr(pos1 + 4, pos - pos1 - 4);
            if (s2.trim().length == 0) {
                let pos3 = s.lastIndexOf('<', pos1 - 1);
                s = s.substr(0, pos1 + 4) + s.substr(pos + 4);
                change = true;
                pos = pos1;
            }
            pos1 = pos;
        }

        if (change) {
            item.innerHTML = s;
        }
    }

    autoResizeText_image(item, subwidth, subheight, minRatio) {
        if (subwidth == 0 && subheight == 0) {
            return;
        }
        let s = item.innerHTML;

        for (let pos = 0;;) {
            let pos2 = s.indexOf("<img ", pos);
            if (pos2 < 0) {
                break;
            }
            let pos3 = s.indexOf(">", pos2);
            if (pos3 < 0) {
                break;
            }
            let s2 = s.substr(pos2, pos3 - pos2) + " ";

            let width = 0, height = 0;
            let posw = s2.indexOf("width=");
            if (posw >= 0) {
                let posw2 = s2.indexOf(" ", posw);
                if (posw2 >= 0) {
                    let num = s2.substr(posw + 6, posw2 - posw - 6).replace("\"", "").replace("\"", "");
                    width = parseInt(num);
                    s2 = s2.substr(0, posw) + s2.substr(posw2);
                }
            }

            posw = s2.indexOf("height=");
            if (posw >= 0) {
                let posw2 = s2.indexOf(" ", posw);
                if (posw2 >= 0) {
                    let num = s2.substr(posw + 7, posw2 - posw - 7).replace("\"", "").replace("\"", "");
                    height = parseInt(num);
                    s2 = s2.substr(0, posw) + s2.substr(posw2);
                }
            }
            if (width > 0 && height > 0) {
                let newWidth = width - subwidth > 0 ? width - subwidth : width / 2;
                let newHeight = height - subheight > 0 ? height - subheight : height / 2;
                let ratio = Math.max(minRatio, Math.min(newWidth / width, newHeight / height));
                s2 = s2 + " width=\"" + Math.round(ratio * width) + "\" height=\"" + Math.round(height * ratio) + "\" ";
            }
            s = s.substr(0, pos2) + s2 + s.substr(pos3);
            pos = pos3;
        }
        item.innerHTML = s;
    }

    getUserGUID() {
        var guid = window.localStorage.getItem('UserGUID');
        if (guid == null || guid == '') {
            guid = this.uuid4();
            window.localStorage.setItem("UserGUID", guid);
        }

        return guid;
    }

    pad(num, size){
        let s = num + "";
        while (s.length < size) s = "0" + s;
        return s;
    }

    uuid4() {
        /// Return the hexadecimal text representation of number `n`, padded with zeroes to be of length `p`
        const ho = (n, p) => this.pad(n.toString(16), p, 0);
        const view = new DataView(new ArrayBuffer(16)); /// Create a view backed by a 16-byte buffer
        crypto.getRandomValues(new Uint8Array(view.buffer)); /// Fill the buffer with random data
        view.setUint8(6, (view.getUint8(6) & 0xf) | 0x40); /// Patch the 6th byte to reflect a version 4 UUID
        view.setUint8(8, (view.getUint8(8) & 0x3f) | 0x80); /// Patch the 8th byte to reflect a variant 1 UUID (version 4 UUIDs are)
        return `${ho(view.getUint32(0), 8)}-${ho(view.getUint16(4), 4)}-${ho(view.getUint16(6), 4)}-${ho(view.getUint16(8), 4)}-${ho(view.getUint32(10), 8)}${ho(view.getUint16(14), 4)}`; /// Compile the canonical textual form from the array data
    }

    computeSizes() {
        if (this.cIcons < 5 || this.cIcons == undefined) {
            this.cIcons = 5;
        }
        this.iconSize = Math.min(window.innerWidth / this.cIcons, window.innerHeight / 5);

        this.iconSize = Math.round(this.iconSize - this.iconSize / 10 / this.cIcons);

        this.padding =  Math.round(this.iconSize / 10);
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
        var r = (x & 0xFF0000) >> 16,
            g = (x & 0x00FF00) >> 8,
            b = x & 0x0000FF;

        return ((r * 299) + (g * 587) + (b * 114)) / 1000;
    }

    getColorGray(x) {
        var r = (x & 0xFF0000) >> 16,
            g = (x & 0x00FF00) >> 8,
            b = x & 0x0000FF,
            yiq = (r * 299) + g * 587 + b * 114,
            m = 255 * 299 + 255 * 587 + 255 * 114,
            gray = Math.round(yiq * 255 / m);

        return (gray << 16) + (gray << 8) + gray;
    }

    getColorMul(x, mul) {
        var r = (x & 0xFF0000) >> 16,
            g = (x & 0x00FF00) >> 8,
            b = x & 0x0000FF;
        r = Math.round(r * mul);
        g = Math.round(g * mul);
        b = Math.round(b * mul);

        return (r << 16) + (g << 8) + b;
    }

    getColorContrast(x) {
        return (this.getContrast(x) >= 128) ? '#000000' : '#ffffff';
    }

    isColorDark(x) {
        return this.getColorContrast(x) == '#ffffff';
    }

    setMessages(a) {
        this.messages = a;
    }

    repairColors(colors) {

        this.colors = colors;
        var instance = this;
        this.colors.sort(function(a, b) {
            return instance.getContrast(a) - instance.getContrast(b);
        });

        this.colorBackground = this.colors[0];
        this.body.style.background = this.getColorHex(this.colorBackground);

        return this.colors;
    }

    computeFontSizeToFit(ctx, text, width, height) {
        let fontName = "";
        let pos = ctx.font.indexOf(" ");
        if (pos >= 0) {
            fontName = ctx.font.substr(pos + 1);
        }

        ctx.font = "1px " + fontName;
    
        let fitFontWidth = Number.MAX_VALUE;
        const lines = text.match(/[^\r\n]+/g);
        lines.forEach(line => {
            fitFontWidth = Math.min(fitFontWidth, width / ctx.measureText(line).width);
        });
        let fitFontHeight = height / (lines.length * 1.2);

        let fontSize = Math.min(fitFontHeight, fitFontWidth);
        ctx.font = fontSize + "px " + fontName;

        return parseInt(ctx.font);
    }

    createKeyboardQwerty(parent, left, top, width, height, numX, cellSize, padding, colorButton, hasBackSpace, hasSpeach) {
        let letters = this.keyboard;
        let xOfs = 0.5 * cellSize;
        if (hasBackSpace) {
            letters += "-\t";
        }

        if (hasSpeach) {
            if ("webkitSpeechRecognition" in window == false) {
                hasSpeach = false;
            } else if (letters.indexOf('#') == -1) {
                letters += "#";
            }
        }

        var cButton = letters.length;
        var aButton = new Array(cButton);

        var sizeButton = cellSize - padding;

        var ix = 0;
        var iy = -1;
        var first = true;
        var fontSize;

        for (let i = 0; i < cButton; i++) {
            if (i % numX == 0) {
                ix = 0;
                iy++;
            }
            var letter = letters[i];
            if (letter == "-") {
                aButton[i] = null;
                ix++;
                continue;
            }

            var x = Math.round(left + ix * cellSize + iy * xOfs) - xOfs;
            var y = Math.round(top + iy * cellSize);
            var btn = this.createTextButton(parent, x, y, Math.round(sizeButton), Math.round(sizeButton), '');
            btn.style.background = this.getColorHex(colorButton);
            btn.style.color = this.getColorContrast(colorButton);
            if (first) {
                btn.innerHTML = "W";
                this.autoResizeText(btn, 0.8 * sizeButton, 0.8 * sizeButton, false, this.minFontSize, this.maxFontSize);
                fontSize = btn.style.fontSize;

                first = false;
            } else {
                btn.style.fontSize = fontSize;
            }
            btn.innerHTML = letter;
            aButton[i] = btn;

            ix++;
        }

        return aButton;
    }

    repairHTML(s, mapFiles, mapFilesWidth, mapFilesHeight) {
        if (s == undefined) {
            return '';
        }
        while (s.substr(0, 4) == '<br>') {
            s = s.substr(4).trim();
        }
        for(;;) {
            let pos = s.indexOf("@@GUID@@");
            if (pos < 0) {
                break;
            }
            let pos2 = s.indexOf("\"", pos);
            if (pos2 > 0) {
                let s2 = s.substr(pos2 + 1);
                let posw = s2.indexOf("width=");
                if (posw != 0) {
                    let posw2 = s2.substr(posw + 1, 1) == "\"" ? s2.indexOf("\"", posw + 2) : s2.indexOf(" ", posw + 2);
                    if (posw2 != 0) {
                        s2 = s2.substr(0, posw) + s2.substr(posw2 + 1);
                    }
                }
                posw = s2.indexOf("height=");
                if (posw != 0) {
                    let posw2 = s2.substr(posw + 1, 1) == "\"" ? s2.indexOf("\"", posw + 2) : s2.indexOf(" ", posw + 2);
                    if (posw2 != 0) {
                        s2 = s2.substr(0, posw) + s2.substr(posw2 + 1);
                    }
                }

                let key = s.substr(pos + 8, pos2 - pos - 8);
                s2 = " width=\"" + mapFilesWidth.get(key) + "\" " + s2;
                s2 = " height=\"" + mapFilesHeight.get(key) + "\" " + s2;

                s = s.substr(0, pos) + "data:image/png;base64, " + mapFiles.get(key) + "\"" + s2;
            }
        }

        return s;
    }

    repairP(s) {
        if (s == undefined) {
            return "";
        }
        let s2 = s.replace(/<p[^>]*>/g, '').replace(/<\/p>/g, '<br />').trim();
        
        let change = true;
        while (change) {
            change = false;
            if (s2.substr(-4) == "<br>") {
                s2 = s2.substr(0, s2.length - 4);
                change = true;
            }
            if (s2.substr(-5) == "<br/>") {
                s2 = s2.substr(0, s2.length - 5);
                change = true;
            }
            if (s2.substr(-6) == "<br/>") {
                s2 = s2.substr(0, s2.length - 6);
                change = true;
            }
        }
        let pos = s2.indexOf('<ol><br></ol>');
        if (pos >= 0) {
            s2 = s2.substr(0, pos) + s2.substr(pos + 13);
        }

        if (s.substr(0, 5) == "<div>") {
            s = s.substr(5);
            if (s.substr(-6, 6) == '</div>') {
                s = s.subtr(0, s.length - 6);
            }
        }

        return s2;
    }

    // Common code
    hideDivScore2() {
        if (this.buttonScore2 != undefined) {
            this.body.removeChild(this.buttonScore2);
        }
    }

    createDivScore(left, top, num, oneLine) {
        var button = document.createElement("button");
        button.style.position = "absolute";
        button.classList.add("mmogame_button");
        button.style.left = left + "px";
        button.style.top = top + "px";
        button.style.width = this.iconSize  + "px";
        button.style.height = this.iconSize + "px";
        button.style.lineHeight = this.iconSize + "px";
        button.style.textAlign = "center";
        button.style.borderRadius=this.iconSize + "px";
        button.style.border = "0px solid " + this.getColorHex(0xFFFFFF);
        button.style.boxShadow = "inset 0 0 0.125em rgba(255, 255, 255, 0.75)";
        button.title = "[LANGM_GRADE]";
        button.alt = "[LANGM_GRADE]";
        if (num == 1) {
            this.buttonScore = button;
            button.style.background = this.getColorHex(this.colorScore);
            button.style.color = this.getColorContrast(this.colorScore);
        } else {
            this.buttonScore2 = button;
            button.style.background = this.getColorHex(this.colorScore2);
            button.style.color = this.getColorContrast(this.colorScore2);
        }

        this.body.appendChild(button);

        button.innerHTML = '100 %';
        this.autoResizeText(button, this.iconSize, this.iconSize, false, 0, 0, 1);
        button.innerHTML = '';

        let h = this.iconSize / 2;
        let div = this.createDiv(this.body, left, top + this.iconSize / 4, this.iconSize, this.iconSize / 2);
        div.style.lineHeight = (this.iconSize / 2) + "px";
        div.style.textAlign = "center";
        div.style.color = this.getColorContrast(this.colorScore);
        div.title = '[LANGM_GRADE]';
        if (num == 1) {
            this.labelScore = div;
        } else {
            this.labelScore2 = div;
        }

        h = this.iconSize / 3;
        div = this.createDiv(this.body, left, top, this.iconSize, h);
        div.style.textAlign = "center";
        div.style.color = this.getColorContrast(this.colorScore);
        button.disabled = true;
        if (num == 1) {
            this.labelScoreRank = div;
        } else {
            this.labelScoreRank2 = div;
        }

        div = this.createDiv(this.body, left, top + this.iconSize - h, this.iconSize, h);
        div.style.textAlign="center";
        div.style.color = this.getColorContrast(this.colorScore);
        div.title = '[LANGM_GRADE_LAST_QUESTION]';
        button.disabled = true;
        if (num == 1) {
            this.labelAddScore = div;
        } else {
            this.labelAddScore2 = div;
        }
    }

    createDivScorePercent1(left, top, num, oneLine) {
        this.createDivScore(left, top, num, oneLine);
    }
    
    createDivScorePercent(left, top, num, oneLine) {

        var button = document.createElement("button");
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
        if (num == 1) {
            this.buttonScore = button;
            button.style.background = this.getColorHex(this.colorScore);
            button.style.color = this.getColorContrast(this.colorScore);
            button.title = "[LANGM_GRADE]";
        } else {
            this.buttonScore2 = button;
            button.style.background = this.getColorHex(this.colorScore2);
            button.style.color = this.getColorContrast(this.colorScore2);
        }

        this.body.appendChild(button);

        button.innerHTML = '';
        button.disabled = true;

        let h = this.iconSize / 2;
        let div  = this.createDiv(this.body, left, top + this.iconSize / 4, this.iconSize / 2, this.iconSize / 2);
        div.style.lineHeight = (this.iconSize / 2) + "px";
        div.style.textAlign = "center";
        div.style.color = this.getColorContrast(this.colorScore);
        if (num == 1) {
            this.labelScore = div;
            div.title = '[LANGM_GRADE]';
        } else {
            this.labelScore2 = div;
            div.title = "[LANGM_GRADE_OPONENT]";
        }

        h = this.iconSize / 3;
        div = this.createDiv(this.body, left, top, this.iconSize / 2, h);
        div.style.textAlign = "center";
        div.style.color = this.getColorContrast(this.colorScore);
        div.title = '[LANGM_RANKING_GRADE]';
        if (num == 1) {
            this.labelScoreRank = div;
        } else {
            this.labelScoreRank2 = div;
        }

        div = this.createDiv(this.body, left, top + this.iconSize - h, this.iconSize / 2, h);
        div.style.textAlign="center";
        div.style.color = this.getColorContrast(this.colorScore);
        div.title = '[LANGM_GRADE_LAST_QUESTION]';
        button.disabled = true;
        if (num == 1) {
            this.labelAddScore = div;
        } else {
            this.labelAddScore2 = div;
        }

        let label = num == 1 ? this.labelScoreRank : this.labelScoreRank2;

        div  = this.createDiv(this.body, left + this.iconSize / 2, top, this.iconSize / 2, h);
        div.style.textAlign = "center";
        div.style.fontName = label.style.fontName;
        div.style.fontSize = h + "px";
        div.style.lineHeight = h + "px";
        div.style.color = label.style.color;
        div.title = '[LANGM_RANKING_PERCENT].';
        if (num == 1) {
            this.labelScoreRankB = div;
        } else {
            this.labelAddScoreRankB2 = div;
        }

        label = num == 1 ? this.labelAddScore : this.labelAddScore2;
        div  = this.createDiv(this.body, left + this.iconSize / 2, parseFloat(this.labelScore.style.top), this.iconSize / 2, this.iconSize / 2);
        div.style.textAlign = "center";
        div.style.lineHeight = Math.round(this.iconSize / 2) + "px";
        div.style.fontName = label.style.fontName;
        div.style.fontSize = label.style.fontSize;
        div.style.fontWeight = 'bold';
        div.style.color = label.style.color;
        div.title = '[LANGM_PERCENT]';
        this.autoResizeText(div, this.iconSize / 2, this.iconSize / 2, false)
        if (num == 1) {
            this.labelScoreB = div;
        } else {
            this.labelScoreB2 = div;
        }
    }

    createDivTimer(left, top, sizeIcon) {
        var div = document.createElement("div");
        div.style.position = "absolute";
        div.style.left = left + "px";
        div.style.top = top + "px";
        div.style.width = sizeIcon + "px";
        div.style.height = sizeIcon + "px";
        div.style.whiteSpace = "nowrap";
        div.style.lineHeight= sizeIcon + "px";
        div.style.textAlign = "center";
        div.style.background = this.getColorHex(this.colorBackground); // "#234025"
        div.style.color = this.getColorContrast(this.colorBackground); // "#DCBFDA"
        div.name = "timer";
        this.body.appendChild(div);
        this.labelTimer = div;

        div.innerHTML = '23:59';
        this.autoResizeText(div, sizeIcon, sizeIcon, false, 0, 0, 1);
        div.innerHTML = '';
        div.title = '[LANGM_QUESTION_TIME]';
    }

    createButtonSound(left, top) {
        this.buttonSound = this.createImageButton(this.body, left, top, this.iconSize, this.iconSize, "mmogame_button_red", this.getMuteFile());
        this.buttonSound.alt = '[LANGM_SOUND]';
        var instance = this;
        this.buttonSound.addEventListener("click", function() {
            instance.onClickSound(this);
        });

        this.buttonSound.title = '[LANGM_SOUND]';
    }

    onClickSound(btn) {
        window.title = btn.src;
        this.kindSound = (parseInt(this.kindSound) + 1) % 2;
        window.localStorage.setItem("kindSound", this.kindSound);
        btn.src = this.getMuteFile();
    }

    createButtonHelp(left, top) {
        this.buttonHelp = this.createImageButton(this.body, left, top, this.iconSize, this.iconSize, "", 'assets/help.svg');
        this.buttonHelp.alt = '[LANGM_HELP]';
    }

    readJsonFiles(json) {
        this.mapFiles = new Map();
        this.mapFilesWidth = new Map();
        this.mapFilesHeight = new Map();
        this.mapFiles.set(json.fileg1, json.filec1);
        this.mapFilesWidth.set(json.fileg1, json.width1);
        this.mapFilesHeight.set(json.fileg1, json.height1);
    }

    findbest(low, up, step1, step2, fn) {
        if (low < 1) {
            low = 1;
        }

        let step = step1;
        if (step1 != step2) {
            for (step = step1; step <= step2; step++) {
                let cmp = fn(low, step);
                if (cmp <= 0) {
                    break;
                }
            }
            if (step > step2) {
                return low;
            }
        }
        let prevSize;
        let fitSize = low;
        let testSize;
        for (var i = 0; i <= 10; i++) {
            prevSize = low;
            testSize = (low + up) / 2;

            let cmp = fn(testSize, step);
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

        this.definitionWidth = width;
        this.definitionHeight = height;
        this.divDefinition = div;

        return [div.scrollWidth - 1, div.scrollHeight];
    }

    onClickDefinition(divDefinition) {
        this.createDivColor(this.body, 0, 0, window.innerWidth, window.innerHeight, this.getColorGray(this.colorBackground));

        var div = document.createElement("div");
        div.style.position = "absolute";
        div.style.left = this.padding + "px";
        div.style.top = this.padding + "px";
        let width = window.innerWidth - 2 * this.padding;
        div.style.width = width + "px";
        let height = window.innerHeight - 2 * this.padding;
        div.style.height = height + "px";
        div.innerHTML = this.repairHTML(this.definition, this.mapFiles, this.mapFilesWidth, this.mapFilesHeight);
        div.style.textAlign = divDefinition.style.textAlign;
        div.style.background = divDefinition.style.background;
        div.style.color = divDefinition.style.color;

        this.autoResizeText(div, width, height, true, this.minFontSize, this.maxFontSize, 0.9);

        this.body.appendChild(div);
    }

    onClickDefinition2(pad, div) {
        this.body.removeChild(pad);
        this.body.removeChild(div);
    }

    updateLabelTimer() {
        if (this.labelTimer == undefined) {
            return;
        }
        if (this.timeclose == 0 || this.timestart == 0) {
            this.labelTimer.innerHTML = '';
            return;
        }

        let dif = Math.round(this.timeclose - ((new Date()).getTime()) / 1000);
        if (dif <= 0) {
            dif = 0;
            this.sendTimeout();
        }
        this.labelTimer.innerHTML = (dif < 0 ? "-" : "") + Math.floor(dif / 60.0) + ":" + ("0" + (dif % 60)).substr(-2);

        if (dif == 0) {
            return;
        }

        let instance = this;
        setTimeout(function(){
            instance.updateLabelTimer();
        }, 500);
    }

    createDivMessage(message) {
        if (this.area != undefined) {
            this.body.removeChild(this.area);
            this.area = undefined;
        }
        
        if (this.divMessageHelp != undefined) {
            this.body.removeChild(this.divMessageHelp);
            this.divMessageHelp = undefined;
        }        

        let left = this.padding;
        let top = this.areaTop;
        let width = window.innerWidth - 2 * this.padding;
        let height = window.innerHeight - this.getCopyrightHeight() - this.padding - top;
        
        if (this.divMessageBackground == undefined) {
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

        if (this.divMessage == undefined) {
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
        this.divMessage.style.top  = (height - this.divMessage.scrollHeight) / 2 + "px";
        
        this.autoResizeText(this.divMessage, width, height, false, this.minFontSize, this.maxFontSize, 0.5);
    }

    createDivMessageStart(message) {
        if (this.area != undefined) {
            this.body.removeChild(this.area);
            this.area = undefined;
        }

        let left = this.padding;
        let top = this.areaTop;
        let width = window.innerWidth - 2 * this.padding;
        let height = window.innerHeight - this.getCopyrightHeight() - this.padding - top;
        
        let height1 = height / 8;

        if (this.divMessageBackground == undefined) {
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

        if (this.divMessage == undefined) {
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
        this.divMessage.style.top  =  top + "px";

        if (this.divMessageHelp == undefined) {
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
        if (this.divMessage != undefined) {
            this.body.removeChild(this.divMessage);
            this.divMessage = undefined;
        }
        if (this.divMessageHelp != undefined) {
            this.body.removeChild(this.divMessageHelp);
            this.divMessageHelp = undefined;
        }
        if (this.divMessageBackground != undefined) {
            this.divMessageBackground.remove();
            this.divMessageBackground = undefined;
        }
    }

    disableButtons(buttons, disabled) {
        for (let i = 0; i < buttons.length; i++) {
            let btn = buttons[i];
            if (btn != undefined) {
                if (disabled) {
                    btn.classList.add("mmogame_imgbutton_disabled");
                } else {
                    btn.classList.remove("mmogame_imgbutton_disabled");
                }
            }
        }
    }

    repairNickname(nickname) {
        if (nickname == undefined) {
            return '';
        }

        let s = nickname
        if (s != '') {
            while (s.indexOf('_') != -1) {
                s = s.replace('_', ' ');
            }
        }

        return s;
    }

    showScore(json) {
        let s = json.sumscore == undefined ? '' : '<b>' + json.sumscore + '</b>';
        if (this.labelScore.innerHTML != s) {
            this.labelScore.innerHTML = s;
            let w = this.iconSize - 2 * this.padding;
            this.autoResizeText(this.labelScore, w, this.iconSize / 2, false, 0, 0, 1);
        }

        if (this.labelScoreRank.innerHTML != json.rank) {
            this.labelScoreRank.innerHTML = json.rank != undefined ? json.rank : '';
            this.autoResizeText(this.labelScoreRank, this.iconSize, this.iconSize / 3, true, 0, 0, 1);
        }

        if (json.name != undefined) {
            window.document.title = (json.usercode != undefined ? json.usercode : "") + " " + json.name;
        }

        s = json.addscore == undefined ? '' : json.addscore;
        if (this.labelAddScore.innerHTML != s) {
            this.labelAddScore.innerHTML = s;
            this.autoResizeText(this.labelAddScore, this.iconSize - 2 * this.padding, this.iconSize / 3, false, 0, 0, 1);
        }
        
        if (this.labelScoreRankB.innerHTML != json.completedrank) {
            this.labelScoreRankB.innerHTML = json.completedrank != undefined ? json.completedrank : '';
            this.autoResizeText(this.labelScoreRankB, 0.9*this.iconSize / 2, this.iconSize / 3, true, 0, 0, 1);
        }
        
        s = json.percentcompleted != undefined  ? Math.round (100 * json.percentcompleted)  + '%' : '';
        if (this.labelScoreB.innerHTML != s) {
            this.labelScoreB.innerHTML = s;
            this.autoResizeText(this.labelScoreB, 0.8 * this.iconSize / 2, this.iconSize / 3, true, 0, 0, 1);
        }
    }

    setBlockServer(block) {
        if (block) {
            this.blockServer = Date.now() + 1000*30;
        } else {
            this.blockServer = 0;
        }
    }

    sendGetAvatars() {
        var xmlhttp = new XMLHttpRequest();
        var instance = this;
        xmlhttp.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {
                instance.OnServerGetAvatars(JSON.parse(this.responseText));
            }
        };

        let countX = Math.floor(this.areaWidth / this.iconSize);
        let countY = Math.floor((this.areaHeight - 2 * this.iconSize - 2 * this.padding) / this.iconSize);
        let count = countX * countY;

        xmlhttp.open("POST", this.url, true);
        xmlhttp.setRequestHeader("Content-Type", "application/json");
        var data = JSON.stringify({"command": "getavatars", "mmogameid": this.mmogameid, "pin" : this.pin,
            "kinduser" : this.kinduser, "user": this.auserid, 'count' : count});
        xmlhttp.send(data);
    }

    createDivInput(parent, left, top, width, height) {
        var div = document.createElement("input");
        div.style.position = "absolute";
        div.style.width = width + "px";
        div.style.type = "text";
        div.style.left = left + "px";
        div.style.top = top + "px";
        div.autofocus = true;

        parent.appendChild(div);

        return div;
    }

    createDivName(left, top, labelWidth, inpWidth, label, value) {
        let div = this.createDiv(this.area, left, top, labelWidth, this.iconSize);
        div.innerHTML = label + ": ";
        div.style.whiteSpace = "nowrap";
        div.style.textAlign = "right";
        div.style.color = this.getColorContrast(this.colorBackground);
        this.autoResizeText(div, labelWidth, this.areaHeight, false, this.minFontSize, this.maxFontSize, 1);

        var divInp = document.createElement("input");
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
        })

        return divInp;
    }

    createCheckbox(parent, left, top, width, height, value) {
        var checkbox = document.createElement('input');
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

    OnServerGetAvatars(json) {
        this.params = true;
        if (this.area != undefined) {
            this.body.removeChild(this.area);
        }
        if (this.divMessage != undefined) {
            this.removeDivMessage();
        }
        this.area = this.createDiv(this.body, this.padding, this.areaTop, this.areaWidth, this.areaHeight);

        let w = this.areaWidth - this.iconSize - this.padding;
        let s = this.buttonsAvatarSrc[1];
        let pos = s.lastIndexOf("/");
        if (pos >= 0) {
            s = s.substr(pos +1);
        }
        pos = s.lastIndexOf(".");
        if (pos >= 0) {
            s = s.substr(0, pos);
        }
        s = this.repairNickname(s);
        let s2 = this.repairNickname(this.nickname);
        if (s == s2) {
            s2 = '';
        }
        let inpNickname = this.createDivName(0, 0, w / 3, 2 * w / 3, "Όνομα", s2);
        inpNickname.value = json.nickname;
        inpNickname.focus();

        let instance = this;
        let btn = this.createImageButton(this.area, w + 2 * this.padding, 0, 0, parseInt(inpNickname.style.fontSize), "", 'assets/submit.svg', false, 'submit');
        btn.addEventListener("click",
            function(){
                instance.sendSetAvatar(inpNickname.value, -1);
            }
        );

        // Colors
        let div = this.createDiv(this.area, 0, this.iconSize, w / 3, this.iconSize);
        div.innerHTML = "[LANGM_PALETTE]:";
        div.style.textAlign = "right";
        div.style.color = this.getColorContrast(this.colorBackground);
        div.style.background = this.colorBackground;
        this.autoResizeText(div, w / 3, this.iconSize, true, this.minFontSize, this.maxFontSize);

        var canvas = document.createElement('canvas');
        canvas.style.position = "absolute";
        canvas.style.left = (w / 3 + this.padding) + "px";
        canvas.style.top = this.iconSize + "px";
        canvas.width = this.iconSize;
        canvas.height = this.iconSize;
        canvas.id = "avatarid";
        this.area.appendChild(canvas);
        this.showColorPalette(canvas, this.colors);
        canvas.addEventListener("click", function(e) {
            instance.sendGetColorPalettes();
        })

        let countX = Math.floor(this.areaWidth / this.iconSize);
        let countY = Math.floor((this.areaHeight - 2 * this.iconSize - 2 * this.padding) / this.iconSize);
        let left = 0;
        let top = 2 * (this.iconSize + this.padding);

        for (let i = 0; i < json.count; i++) {
            let btn = this.createCenterImageButton(this.area, left, top, this.iconSize, this.iconSize, "",
                'assets/avatars/' + json['avatar' + (i + 1)]);
            btn.addEventListener("click",
                function() {
                    instance.sendSetAvatar(inpNickname.value, json['id' + (i + 1)]);
                }
            );
            left += this.iconSize;

            if ((i + 1) % countX == 0) {
                top += this.iconSize;
                left = 0;
            }
        }
    }

    showColorPalette(canvas, colors) {
        var ctx = canvas.getContext("2d");
        var width = canvas.width;
        var height = canvas.height;
        var strip = width / 5;
        let instance = this;

        colors.sort(function(a, b) {
            return instance.getContrast(a) - instance.getContrast(b);
        });
        for (var i = 0; i < 5; i++) {
            ctx.fillStyle = this.getColorHex(colors[i]);
            ctx.fillRect(i * strip, 0, (i + 1) * strip, height);
        }

        ctx.strokeStyle = "#FFFFFF";
        ctx.lineWidth = 1;
        ctx.strokeRect(0, 0, width, height);
    }

    sendSetAvatar(nickname, avatarid) {
        var xmlhttp = new XMLHttpRequest();
        var instance = this;
        xmlhttp.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {
                let json = JSON.parse(this.responseText);
                instance.updateButtonsAvatar(1, json.avatar);
                instance.sendGetAttempt();
            }
        }

        let countX = Math.floor(this.areaWidth / this.iconSize);
        let countY = Math.floor((this.areaHeight - 2 * this.iconSize - 2 * this.padding) / this.iconSize);

        xmlhttp.open("POST", this.url, true);
        xmlhttp.setRequestHeader("Content-Type", "application/json");
        var data = JSON.stringify({"command": "setavatar", "mmogameid": this.mmogameid, "pin" : this.pin,
            'kinduser': this.kinduser, "user": this.auserid, 'avatarid': avatarid, 'nickname': nickname});
        xmlhttp.send(data);
    }

    sendGetColorPalettes() {
        var xmlhttp = new XMLHttpRequest();
        var instance = this;
        xmlhttp.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {
                let json = JSON.parse(this.responseText);
                instance.createScreenColorPalette(json);
            }
        };

        let countX = Math.floor(this.areaWidth / this.iconSize);
        let countY = Math.floor(this.areaHeight / this.iconSize);

        xmlhttp.open("POST", this.url, true);
        xmlhttp.setRequestHeader("Content-Type", "application/json");
        var data = JSON.stringify({"command": "getcolorpalettes", "mmogameid": this.mmogameid, "pin": this.pin,
            'kinduser': this.kinduser, "user": this.auserid, "count": countX * countY});
        xmlhttp.send(data);
    }

    sendSetColorPalette(colorpaletteid) {
        var xmlhttp = new XMLHttpRequest();
        var instance = this;
        xmlhttp.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {
                instance.colors = undefined;
                instance.openGame(instance.url, instance.mmogameid, instance.pin, instance.auserid, instance.kinduser);
            }
        };

        xmlhttp.open("POST", this.url, true);
        xmlhttp.setRequestHeader("Content-Type", "application/json");
        var data = JSON.stringify({"command": "setcolorpalette", "mmogameid": this.mmogameid, "pin": this.pin,
            'kinduser': this.kinduser, "user": this.auserid, "id": colorpaletteid});
        xmlhttp.send(data);
    }

    createScreenColorPalette(json) {
        if (this.area != undefined) {
            this.body.removeChild(this.area);
        }
        if (this.divMessage != undefined) {
            this.body.removeChild(this.divMessage);
            this.divMessage = undefined;
        }
        if (this.divMessageHelp != undefined) {
            this.divMessageHelp.style.height = "0px";
            this.body.removeChild(this.divMessageHelp);
            this.divMessageHelp = undefined;
        }
        this.area = this.createDiv(this.body, this.padding, this.areaTop, this.areaWidth, this.areaHeight);
        let countX = Math.floor(this.areaWidth / (this.iconSize + this.padding));
        let countY = Math.floor(this.areaHeight / (this.iconSize + this.padding));

        let instance = this;
        let i = 0;
        for (let iy = 0; iy < countY; iy++) {
            for (let ix = 0; ix < countX; ix++) {
                i++;
                if (i > json.count) {
                    break;
                }
                let canvas = document.createElement('canvas');
                canvas.style.position = "absolute";
                canvas.style.left = ((ix % countX) * (this.iconSize + this.padding)) + "px";
                canvas.style.top = (iy * (this.iconSize + this.padding)) + "px";
                canvas.width = this.iconSize;
                canvas.height = this.iconSize;
                canvas.style.cursor = 'pointer';
                this.area.appendChild(canvas);
                let a = json["palette" + i];
                for (let j = 0; j < a.length; j++) {
                    a[j] = parseInt(a[j]);
                }
                this.showColorPalette(canvas, a);
                let id = json['id' + i];

                canvas.addEventListener("click", function(e){
                                                                instance.sendSetColorPalette(id);
                                                            });
            }
        }
        this.area.classList.add("palete");
    }

    setColorsString(s) {
        let a = [0x9B7ED9, 0x79F2F2, 0x67BF5A, 0xD0F252, 0xBF5B21];
        if (s != undefined && s.length >= 0) {
            let b = s.split(",");
            if (b.length == 5) {
                a = b;
                for (let i = 0; i < 5; i++) {
                    a[i] = parseInt(a[i]);
                }
            }
        }
        this.setColors(a);
    }

    computeDifClock(json) {
        if (json.time != undefined) {
            this.difClock = ((new Date()).getTime() - json.time) / 1000;
        }

        this.computeTimeStartClose(json);
    }

    computeTimeStartClose(json) {
        if (json.timestart != undefined) {
            this.timestart = parseInt(json.timestart) != 0 ? parseInt(json.timestart) + this.difClock : 0;
            this.timeclose = parseInt(json.timeclose) != 0 ? parseInt(json.timeclose) + this.difClock : 0;
        } else {
            this.timestart = 0;
            this.timeclose = 0;
        }

        let tempTime = (new Date()).getTime() / 1000;
        let tempRest = this.timeclose - tempTime;
    }

    changeToDisabledCheckRadio(item, color1, color2) {
        let div = this.createRadiobox(item.parentElement, item.clientWidth, color1, color2, item.checked);
        div.style.position = item.style.position;
        div.style.left = item.style.left;
        div.style.top = item.style.top;

        item.style.visibility = "hidden";

        return div;
    }

    drawRadio(canvas, color1, color2) {
        var ctx = canvas.getContext("2d");
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
        var canvas = document.createElement('canvas');
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

    createCheckboxCanvas(parent, size, color1, color2, checked, disabled) {
        var canvas = document.createElement('canvas');
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

        this.drawCombobox(canvas, color1, color2);

        return canvas;
    }

    drawCombobox(canvas, color1, color2) {
        var ctx = canvas.getContext("2d");
        let size = canvas.width;
        ctx.clearRect(0, 0, size, canvas.height);

        ctx.beginPath();
        ctx.strokeStyle = this.getColorHex(color1);
        ctx.lineWidth = Math.round(size / 10);
        ctx.rect(0, 0, size, size);
        ctx.stroke();

        let checked = canvas.classList.contains("checked");
        if (checked) {
            ctx.fillStyle = this.getColorHex(color1);
            ctx.rect(0, 0, size, size);
            ctx.fill();

            ctx.beginPath();
            ctx.strokeStyle = this.getColorHex(0xFFFFFF);
            let width = Math.round(size / 7);
            ctx.lineWidth = width;
            let dx = size / 4;
            let dy = size / 4;
            ctx.moveTo(dx, size / 2);
            ctx.lineTo(size / 2, size - dy - width / 2);
            ctx.lineTo(size - dx, dy);
            ctx.stroke();
        }
    }

    createButtonsAvatar(num, left, widthNickName = 0, heightNickName = 0) {
        if (widthNickName == 0) {
            widthNickName = this.iconSize;
        }
        if (heightNickName == 0) {
            heightNickName = this.iconSize - this.buttonAvatarHeight;
        }
        if (this.buttonsAvatarLeft == undefined) {
            this.buttonsAvatarLeft = [];
        }
        this.buttonsAvatarLeft[num] = left;

        if (this.buttonsAvatarSrc == undefined) {
            this.buttonsAvatarSrc = [];
        }
        this.buttonsAvatarSrc[num] = "";

        if (this.nicknames == undefined) {
            this.nicknames = [];
        }
        this.nicknames[num] = "";

        if (this.buttonsAvatar == undefined) {
            this.buttonsAvatar = [];
        }
        this.buttonsAvatar[num] = this.createImageButton(this.body, left, this.avatarTop, this.iconSize, this.iconSize, "", "");
        if (num == 2 && this.avatarTop != undefined) {
            this.buttonsAvatar[num].title = '[LANGM_OPONENT]'
        }

        if (this.divNicknames == undefined) {
            this.divNicknames = [];
        }
        if (this.divNicknamesWidth == undefined) {
            this.divNicknamesWidth = [];
        }
        if (this.divNicknamesHeight == undefined) {
            this.divNicknamesHeight = [];
        }        
        this.divNicknamesWidth[num] = widthNickName;
        this.divNicknamesHeight[num] = heightNickName;

        this.divNicknames[num] = this.createDiv(this.body, left, this.padding, widthNickName, heightNickName);
    }

    updateButtonsAvatar(num, avatar, nickname) {

        if (avatar == undefined) {
            avatar = "";
        }
        if (nickname == undefined) {
            nickname = "";
        }

        if (avatar == "" && nickname == "") {
            this.buttonsAvatar[num].style.visibility = 'hidden';
            this.divNicknames[num].style.visibility = 'hidden';
            return;
        }
        
        if (this.nicknames[num] != nickname || nickname == "") {
            let instance = this;

            this.nicknames[num] = nickname;
            let s = nickname;

            if (nickname.length == 0) {
                s = avatar;
                let pos = s.lastIndexOf("/");
                if (pos >= 0) {
                    s = s.substr(pos + 1);
                }
                pos = s.lastIndexOf(".");
                if (pos >= 0) {
                    s = s.substr(0, pos);
                }
            }
            s = this.repairNickname(s);
            if (this.divNicknames[num] != undefined && this.divNicknames[num].innerHTML != s) {
                this.divNicknames[num].innerHTML = s;
                this.divNicknames[num].style.textAlign = "center";
                this.divNicknames[num].style.color = this.getColorContrast(this.colorsBackground);
                this.autoResizeText(this.divNicknames[num], this.divNicknamesWidth[num], this.divNicknamesHeight[num], true, 0, 0, 1);
            }
        }

        if (avatar != this.buttonsAvatarSrc[num]) {
            this.updateImageButton(this.buttonsAvatar[num], this.buttonsAvatarLeft[num], this.buttonAvatarTop, this.iconSize, this.buttonAvatarHeight, avatar != "" ? "assets/avatars/" + avatar : "");
            this.buttonsAvatarSrc[num] = avatar;
        }

        this.buttonsAvatar[num].alt = this.divNicknames[num].innerHTML;

        this.buttonsAvatar[num].style.visibility = 'visible';
        this.divNicknames[num].style.visibility = 'visible';
    }

    getSVGprev(size, color) {
        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 667 667"><defs><clipPath id="a"><path clip-rule="evenodd" d="M500 0L500 500L500 500L0 500L0 500L0 0L0 0L500 0z"/></clipPath></defs><g clip-path="url(#a)" transform="scale(1.3333)"><path transform="matrix(1 0 0 -1 0 500)" d="M256.77502 468.5281L256.77502 468.5281L256.77502 468.5281L256.77502 468.5281L256.77502 468.5281L445.66214 468.5281L445.66214 468.5281L238.84933 261.7153L238.84933 261.7153L238.84933 261.7153C 235.60168 258.46765 234.11804 254.18806 234.24268 249.92534L234.24268 249.92534L234.24268 249.92534C 234.11804 245.67822 235.60168 241.39864 238.84933 238.15097L238.84933 238.15097L445.6933 31.290157L445.6933 31.290157L445.6933 31.290157L445.6933 31.290157L256.72833 31.290157L256.72833 31.290157L38.12555 249.89291L38.12555 249.89291L256.77502 468.5281zM4.5989075 238.15099L4.5989075 238.15099L4.5989075 238.15099L237.95758 4.791005L237.95758 4.791005L237.95758 4.791005C 241.11176 1.6368077 245.24988 0.09086167 249.3724 0.12201445L249.3724 0.12201445L249.3724 0.12201445C 249.49702 0.12201445 249.59048 0.05970932 249.71638 0.05970932L249.71638 0.05970932L249.71638 0.05970932L249.71638 0.05970932L483.95123 0.05970932L483.95123 0.05970932L483.95123 0.05970932C 492.5714 0.05970932 499.56647 7.0560565 499.56647 15.674933L499.56647 15.674933L499.56647 15.674933C 499.56647 15.799542 499.50415 15.893002 499.50415 16.01891L499.50415 16.01891L499.50415 16.01891C 499.51974 20.141432 497.9738 24.27953 494.83517 27.433727L494.83517 27.433727L272.3747 249.89421L272.3747 249.89421L495.30374 472.822L495.30374 472.822L495.30374 472.822C 501.56543 479.08368 501.56543 489.2342 495.30374 495.49588L495.30374 495.49588L495.30374 495.49588C 491.58752 499.21213 486.5434 500.44656 481.71863 499.7586L481.71863 499.7586L481.71863 499.7586L481.71863 499.7586L251.99702 499.7586L251.99702 499.7586L251.99702 499.7586C 247.17227 500.44528 242.12814 499.2122 238.4119 495.49588L238.4119 495.49588L4.614502 261.6985C 1.3668518 258.45084 -0.11682129 254.17125 0.0078125 249.90852L0.0078125 249.90852L0.0078125 249.90852C -0.11810303 245.67693 1.3655396 241.39864 4.5989075 238.15099z" stroke="#FFF" stroke-width="1.731" fill="' +
            this.getColorHex(color) + '"/></g></svg>';
    }

    getSVGnext(size, color) {
        return '<svg xmlns="http://www.w3.org/2000/svg" x="0px" y="0px" viewBox="0 0 385.201 385.201"><path fill="' +
            this.getColorHex(color) +
            '" d="M381.658,183.472L201.878,3.691c-2.43-2.43-5.618-3.621-8.794-3.597 c-0.096,0-0.168-0.048-0.265-0.048H12.364c-6.641,0-12.03,5.39-12.03,12.03c0,0.096,0.048,0.168,0.048,0.265 c-0.012,3.176,1.179,6.364,3.597,8.794l171.384,171.384L3.618,364.263c-4.824,4.824-4.824,12.644,0,17.468 c2.863,2.863,6.749,3.814,10.466,3.284h176.978c3.717,0.529,7.603-0.421,10.466-3.284l180.118-180.118 c2.502-2.502,3.645-5.799,3.549-9.083C385.292,189.27,384.149,185.974,381.658,183.472z M187.381,360.955H41.862l159.329-159.329 c2.502-2.502,3.645-5.799,3.549-9.083c0.096-3.272-1.047-6.569-3.549-9.071L41.838,24.106h145.579l168.412,168.412L187.381,360.955 z"/></svg>';
    }

    setHelpURL(url) {
        this.helpUrl = url;
    }
}