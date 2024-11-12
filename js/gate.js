// eslint-disable-next-line no-unused-vars, no-undef
class mmogameGate extends mmogame {
    open(url, mmogameid, pin, auserid, kinduser) {
        this.minFontSize *= 2;
        this.maxFontSize *= 2;

        this.url = url;
        this.mmogameid = mmogameid;
        this.pin = pin;
        this.auserid = auserid;
        this.kinduser = kinduser;
        this.computeSizes();
        this.areaTop = this.padding;
        this.copyrightHeight = 0;

        this.areaWidth = Math.round(window.innerWidth - 2 * this.padding);
        this.areaHeight = Math.round(window.innerHeight - this.areaTop) - this.padding;

        switch (kinduser) {
            case 'moodle':
                if (localStorage.getItem("nickname") !== null && localStorage.getItem("avatarid") !== null
                && localStorage.getItem("paletteid") !== null) {
                    let avatarid = parseInt(localStorage.getItem("avatarid"));
                    let paletteid = parseInt(localStorage.getItem("paletteid"));
                    this.playgame(auserid, localStorage.getItem("nickname"), paletteid, avatarid);
                    return;
                }
                break;
            case 'guid':
                if (localStorage.getItem("auserid") !== null && localStorage.getItem("nickname") !== null
                && localStorage.getItem("avatarid") !== null && localStorage.getItem("paletteid") !== null) {
                    let avatarid = parseInt(localStorage.getItem("avatarid"));
                    let paletteid = parseInt(localStorage.getItem("paletteid"));
                    this.playgame(localStorage.getItem("auserid"), localStorage.getItem("nickname"), paletteid, avatarid);
                    return;
                }
                break;
        }

        this.createScreen();
    }

    createScreen() {
        this.vertical = window.innerWidth < window.innerHeight;

        if (this.area != undefined) {
            this.body.removeChild(this.area);
            this.area = undefined;
        }
        this.removeDivMessage();

        this.area = this.createDiv(this.body, this.padding, this.areaTop, this.areaWidth, this.areaHeight);

        if (this.vertical) {
            this.createScreenVertical();
        } else {
            this.createScreenHorizontal();
        }
    }

    createScreenVertical() {
        let maxHeight = this.areaHeight - 5 * this.padding - this.iconSize;
        let maxWidth = this.areaWidth;
        let instance = this;
        let size;

        this.fontSize = this.findbest(this.minFontSize, this.maxFontSize, function(fontSize) {
            size = instance.computeLabelSize(fontSize, ['[LANGM_CODE]: ', '[LANGM_NAME]: ', '[LANGM_PALETTE]']);

            if (size[0] >= maxWidth) {
                return 1;
            }
            let heightCode = instance.kinduser != 'guid' && instance.kinduser != 'moodle' ? size[1] + instance.padding : 0;

            let heightColors = (maxHeight - 4 * fontSize) * 2 / 5;
            let n = Math.floor(heightColors / instance.iconSize);
            if (n == 0) {
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
        if (this.kinduser != 'guid' && this.kinduser != 'moodle') {
            bottom = this.createCode(0, 0, maxWidth, this.fontSize, size[0]);
            this.edtCode = this.edt;
            this.edtCode.addEventListener("keyup", function() {
                instance.updateSubmit();
            });
        } else {
            bottom = 0;
        }
        bottom = this.createLabelEditVertical(0, bottom, newWidth - 2 * this.padding, this.fontSize, size[0],
            "[LANGM_NAME]: ") + 2 * this.padding;
        this.edtNickname = this.edt;
        this.edtNickname.addEventListener("keyup", function() {
            instance.updateSubmit();
        });

        let label1 = document.createElement("label");
        label1.style.position = "absolute";
        label1.innerHTML = "[LANGM_PALETTE]";
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

                instance.sendGetColorsAvatarsVertical(0, bottom, gridWidthColors, gridHeightColors,
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
        label.innerHTML = "[LANGM_AVATARS]";
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

                instance.sendGetColorsAvatarsVertical(0, bottom, gridWidthColors, gridHeightColors,
                    0, bottom + gridHeightColors + instance.fontSize + instance.padding, gridWidthAvatars, gridHeightAvatars,
                    false, true);
            }
        );

        label.style.left = "0 px";
        label.style.color = this.getColorContrast(this.colorBackground);
        label.style.top = (bottom + gridHeightColors) + "px";

        // Vertical
        this.sendGetColorsAvatarsVertical(0, bottom, gridWidthColors, gridHeightColors,
                                          0, bottom + gridHeightColors + this.fontSize + this.padding, gridWidthAvatars,
                                          gridHeightAvatars);

        let bottom2 = bottom + gridHeightColors + this.fontSize + this.padding + gridHeightAvatars;
        this.btnSubmit = this.createImageButton(this.area, (maxWidth - this.iconSize) / 2, bottom2, 0,
            this.iconSize, "", 'assets/submit.svg', false, 'submit');
        this.btnSubmit.style.visibility = 'hidden';
        this.btnSubmit.addEventListener("click", function() {
            instance.playgame(instance.edtCode == undefined ? instance.auserid : instance.edtCode.value,
            instance.edtNickname.value, instance.paletteid, instance.avatarid);
        });
    }

    createScreenHorizontal() {
        let maxHeight = this.areaHeight - 7 * this.padding - this.iconSize;
        let maxWidth = this.areaWidth;
        let instance = this;
        let size;

        this.fontSize = this.findbest(this.minFontSize, this.maxFontSize, function(fontSize) {
            size = instance.computeLabelSize(fontSize, ['[LANGM_CODE]: ', '[LANGM_NAME]: ', '[LANGM_PALETTE]']);

            if (size[0] >= maxWidth) {
                return 1;
            }
            let heightCode = instance.kinduser != 'guid' && instance.kinduser != 'moodle' ? size[1] + instance.padding : 0;

            let heightColors = (maxHeight - 4 * fontSize) * 2 / 5;
            let n = Math.floor(heightColors / instance.iconSize);
            if (n == 0) {
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
        if (this.kinduser != 'guid' && this.kinduser != 'moodle') {
            bottom = this.createCode(0, 0, maxWidth, this.fontSize, size[0]);
            this.edtCode = this.edt;
            this.edtCode.addEventListener("keyup", function() {
                instance.updateSubmit();
            });
        } else {
            bottom = 0;
        }
        let sizeLabel = this.computeLabelSize(this.fontSize, ['[LANGM_NAME]: ']);
        bottom = this.createLabelEdit(0, bottom, newWidth - 2 * this.padding, this.fontSize, sizeLabel[0], "[LANGM_NAME]: ");

        this.edtNickname = this.edt;
        this.edtNickname.addEventListener("keyup", function() {
            instance.updateSubmit();
        });

        let label1 = document.createElement("label");
        label1.style.position = "absolute";
        label1.innerHTML = "[LANGM_PALETTE]";
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

                instance.sendGetColorsAvatarsVertical(0, bottom, gridWidthColors, gridHeightColors, 0,
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
        label.innerHTML = "[LANGM_AVATARS]";
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

                instance.sendGetColorsAvatarsVertical(0, bottom, gridWidthColors, gridHeightColors, 0,
                    bottom + gridHeightColors + instance.fontSize + instance.padding, gridWidthAvatars, gridHeightAvatars,
                    false, true);
            }
        );

        // Avatar
        label.style.left = "0 px";
        label.style.color = this.getColorContrast(this.colorBackground);
        label.style.top = (bottom + gridHeightColors) + "px";

        // Horizontal
        this.sendGetColorsAvatarsVertical(0, bottom, gridWidthColors, gridHeightColors,
                                          0, bottom + gridHeightColors + this.fontSize + this.padding, gridWidthAvatars,
                                          gridHeightAvatars);

        let bottom2 = bottom + gridHeightColors + this.fontSize + this.padding + gridHeightAvatars;
        this.btnSubmit = this.createImageButton(this.area, (maxWidth - this.iconSize) / 2, bottom2, 0,
            this.iconSize, "", 'assets/submit.svg', false, 'submit');
        this.btnSubmit.style.visibility = 'hidden';
        this.btnSubmit.addEventListener("click",
            function() {
                instance.playgame(instance.edtCode == undefined ? instance.auserid : instance.edtCode.value,
                instance.edtNickname.value, instance.paletteid, instance.avatarid);
            });
    }

    computeLabelSize(fontSize, aLabel) {
        let maxWidth = 0;
        let maxHeight = 0;

        for (let i = 0; i < aLabel.length; i++) {
            var label = document.createElement("label");
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

    createLabelEdit(left, top, width, fontSize, labelWidth, title) {
        var label = document.createElement("label");
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
        var div = document.createElement("input");
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

    createLabelEditVertical(left, top, width, fontSize, labelWidth, title) {
        var label = document.createElement("label");
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
        var div = document.createElement("input");
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

    createCode(left, top, width, fontSize, labelWidth) {
        return this.createLabelEdit(left, top, width, fontSize, labelWidth, "[LANGM_CODE]: ");
    }

    createNickName(left, top, width, fontSize, labelWidth) {
        return this.createLabelEdit(left, top, width, fontSize, labelWidth, "[LANGM_NAME]: ");
    }

    showAvatars(json, left, top, width, height, countX) {
        this.avatar = undefined;
        let leftOriginal = left;
        let instance = this;
        let w = Math.round(this.padding / 2) + "px";
        for (let i = 0; i < json.countavatars; i++) {
            let btn = this.createCenterImageButton(this.area, left, top, this.iconSize - this.padding,
                this.iconSize - this.padding, "", 'assets/avatars/' + json['avatar' + (i + 1)]);
            btn.classList.add("mmogame_avatar");
            let id = json['avatarid' + (i + 1)];
            btn.addEventListener("click",
                function() {
                    instance.updateAvatar(btn, id, w);
                }
            );

            left += this.iconSize;

            if ((i + 1) % countX == 0) {
                top += this.iconSize;
                left = leftOriginal;
            }
        }
    }

    sendGetColorsAvatars(left, top, gridWidthColors, gridHeight, gridWidthAvatars) {
        let gridWidthColors0 = gridWidthColors;
        let countXcolors = Math.floor(gridWidthColors / this.iconSize);
        let countYcolors = Math.floor((gridHeight + 2 * this.padding) / this.iconSize);
        gridWidthColors = countXcolors * this.iconSize;

        gridWidthAvatars += gridWidthColors0 - gridWidthColors;
        let countXavatars = Math.floor(gridWidthAvatars / this.iconSize);
        let countYavatars = Math.floor((gridHeight + 2 * this.padding) / this.iconSize);

        var xmlhttp = new XMLHttpRequest();
        var instance = this;
        xmlhttp.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {
                let json = JSON.parse(this.responseText);
                instance.showColorPalettes(json, left, top, gridWidthColors, gridHeight, countXcolors, countYcolors);
                instance.showAvatars(json, left + gridWidthColors + instance.padding, top, gridWidthAvatars, gridHeight,
                    countXavatars);
            }
        };

        xmlhttp.open("POST", this.url, true);
        xmlhttp.setRequestHeader("Content-Type", "application/json");
        var data = JSON.stringify({"command": "getcolorsavatars", "mmogameid": this.mmogameid, "pin": this.pin,
            'kinduser': this.kinduser, "user": this.auserid, "countcolors": countXcolors * countYcolors,
            "countavatars": countXavatars * countYavatars});
        xmlhttp.send(data);
    }

    sendGetColorsAvatarsVertical(leftColors, topColors, gridWidthColors, gridHeightColors, leftAvatars, topAvatars,
    gridWidthAvatars, gridHeightAvatars, updateColors = true, updateAvatars = true) {

        let countXcolors = Math.floor(gridWidthColors / this.iconSize);
        let countYcolors = Math.floor(gridHeightColors / this.iconSize);
        gridWidthColors = countXcolors * this.iconSize;

        let countXavatars = Math.floor(gridWidthAvatars / this.iconSize);
        let countYavatars = Math.floor((gridHeightAvatars + 2 * this.padding) / this.iconSize);
        gridWidthAvatars = countXavatars * this.iconSize;

        if (!updateColors) {
            countXcolors = countXcolors = 0;
        }
        if (!updateAvatars) {
            countXavatars = countYavatars = 0;
        }

        var xmlhttp = new XMLHttpRequest();
        var instance = this;
        xmlhttp.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {
                let json = JSON.parse(this.responseText);
                if (updateColors) {
                    instance.showColorPalettes(json, leftColors, topColors, gridWidthColors, gridHeightColors,
                        countXcolors, countYcolors);
                }
                if (updateAvatars) {
                    instance.showAvatars(json, leftAvatars, topAvatars, gridWidthAvatars, gridHeightAvatars, countXavatars);
                }
            }
        };

        xmlhttp.open("POST", this.url, true);
        xmlhttp.setRequestHeader("Content-Type", "application/json");
        var data = JSON.stringify({"command": "getcolorsavatars", "mmogameid": this.mmogameid, "pin": this.pin,
            'kinduser': this.kinduser, "user": this.auserid, "countcolors": countXcolors * countYcolors,
            "countavatars": countXavatars * countYavatars});
        xmlhttp.send(data);
    }

    showColorPalettes(json, left, top, width, height, countX, countY) {
        let instance = this;
        let i = 0;
        this.canvasColor = undefined;
        for (let iy = 0; iy < countY; iy++) {
            for (let ix = 0; ix < countX; ix++) {
                i++;
                if (i > json.count) {
                    break;
                }
                let a = json["palette" + i];
                if (a == undefined) {
                    break;
                }
                let canvas = document.createElement('canvas');
                canvas.style.position = "absolute";
                canvas.style.left = (left + (ix % countX) * this.iconSize) + "px";
                canvas.style.top = (top + iy * this.iconSize) + "px";
                canvas.width = this.iconSize - this.padding * 3 / 2;
                canvas.height = this.iconSize - this.padding * 3 / 2;
                canvas.style.cursor = 'pointer';
                canvas.classList.add("mmogame_color");
                this.area.appendChild(canvas);
                for (let j = 0; j < a.length; j++) {
                    a[j] = parseInt(a[j]);
                }
                this.showColorPalette(canvas, a);
                let id = json['paletteid' + i];

                canvas.addEventListener("click", function() {
                    instance.updateColorPalete(canvas, id);
                });
            }
        }
        this.area.classList.add("palete");
    }

    updateColorPalete(canvas, id) {
        if (this.canvasColor != undefined) {
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

        this.updateSubmit();
    }

    updateAvatar(avatar, id, w) {
        if (this.avatar != undefined) {
            this.avatar.style.borderStyle = "none";
        }
        this.avatar = avatar;
        avatar.style.borderStyle = "outset";

        avatar.style.borderLeftWidth = w;
        avatar.style.borderTopWidth = w;
        avatar.style.borderRightWidth = w;
        avatar.style.borderBottomWidth = w;

        this.avatarid = id;

        this.updateSubmit();
    }

    updateSubmit() {
        let hasCode = this.edtCode == undefined ? true : parseInt(this.edtCode.value) > 0;
        let visible = this.avatarid != undefined && this.paletteid != undefined && hasCode && this.edtNickname.value.length > 0;

        this.btnSubmit.style.visibility = visible ? 'visible' : 'hidden';
    }

    playgame(auserid, nickname, paletteid, avatarid) {
        if (auserid == 0 && this.kinduser == 'guid') {
            auserid = this.getUserGUID();
        }
        localStorage.setItem("auserid", auserid);
        localStorage.setItem("nickname", nickname);
        localStorage.setItem("paletteid", paletteid);
        localStorage.setItem("avatarid", avatarid);

        var xmlhttp = new XMLHttpRequest();
        var instance = this;
        xmlhttp.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {
                instance.onServerGetAttempt(JSON.parse(this.responseText), auserid);
            }
        };

        xmlhttp.open("POST", this.url, true);
        xmlhttp.setRequestHeader("Content-Type", "application/json");
        let d = {"command": "getattempt", "mmogameid": this.mmogameid, "pin": this.pin, "kinduser": this.kinduser,
            "user": auserid, "nickname": nickname, "paletteid": paletteid, "avatarid": avatarid,
            "maxwidth": this.maxImageWidth, "maxheight": this.maxImageHeight};
        if (this.helpurl == undefined) {
            d.helpurl = 1;
        }
        let data = JSON.stringify(d);
        xmlhttp.send(data);
    }

    onServerGetAttempt(json, auserid) {
        if (json.errorcode != undefined) {
            return;
        }
        // eslint-disable-next-line no-undef
        let game = new [CLASS]();
        game.openGame(this.url, this.mmogameid, this.pin, auserid, this.kinduser, false);
        game.onServerGetAttempt(json);
    }

    computeSizes() {
        super.computeSizes();
        this.iconSize = Math.round(0.8 * this.iconSize);
        this.padding = Math.round(0.8 * this.padding);
    }
}
