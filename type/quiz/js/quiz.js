class mmogameQuiz extends mmogame {
    constructor() {
        super();
        this.hideSubmit = false;
    }

    setColors(colors, nameLoad) {
        let c = this.repairColors(colors, nameLoad);

        this.colorDefinition = c[1];
        this.colorScore = c[2];
        this.colorCopyright = c[3];
        this.colorScore2 = c[4];
    }

    openGame(url, id, pin, auserid, kinduser, callOnAfterOpenGame) {
        super.openGame(url, id, pin, auserid, kinduser, callOnAfterOpenGame);

        this.audioYes = new Audio('assets/yes1.mp3');
        this.audioYes.preload = true;
        this.audioNo = new Audio('assets/no1.mp3');
        this.audioNo.preload = true;
    }

    onAfterOpenGame() {
        this.sendGetAttempt();
    }

    sendGetAttempt(param, subcommand) {
        var xmlhttp = new XMLHttpRequest();
        var instance = this;
        xmlhttp.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {
                instance.onServerGetAttempt(JSON.parse(this.responseText), param, subcommand);
            }
        };

        xmlhttp.open("POST", this.url, true);
        xmlhttp.setRequestHeader("Content-Type", "application/json");
        let d = {"command": "getattempt", "mmogameid": this.mmogameid, "pin": this.pin, "kinduser": this.kinduser,
            "user": this.auserid, "maxwidth": this.maxImageWidth, "maxheight": this.maxImageHeight, "subcommand": subcommand};
        if (this.helpurl == undefined) {
            d['helpurl'] = 1;
        }
        let data = JSON.stringify(d);
        xmlhttp.send(data);
    }

    onServerGetAttempt(json, param, subcommand) {
        this.computeDifClock(json);

        if (this.colors == undefined) {
            this.setColorsString(json.colors);
            this.createIconBar();
        }

        if (json.name != undefined) {
            window.document.title = json.name;
        }

        if (json.helpurl != undefined) {
            this.helpUrl = json.helpurl;
        }

        if (json.errorcode != undefined) {
            this.createDivMessage(json.errorcode);
            return;
        }

        this.state = json.state;
        this.fastjson = json.fastjson;
        this.timefastjson = json.timefastjson;
        this.updateButtonsAvatar(1, json.avatar, json.nickname);

        this.attempt = json.attempt;

        this.qtype = json.qtype;
        if (json.qtype == 'multichoice') {
            this.answers = [];
            this.answersID = [];
            for (let i = 1; i <= json.answers; i++) {
                this.answersID.push(json["answerid_" + i]);
                this.answers.push(this.repairP(json["answer_" + i]));
            }
        }
        this.answer = json.answer != undefined ? json.answer : null;
        this.endofgame = json.endofgame != undefined && json.endofgame != 0;
        this.definition = this.repairP(json.definition);
        this.single = json.single;
        this.errorcode = json.errorcode;

        this.readJsonFiles(json);
        if (json.state != 0) {
            this.createScreen(json, false);
        }

        this.updateLabelTimer();

        this.sendFastJSON();
    }

    updateLabelTimer() {
        if (this.labelTimer == undefined || this.timeclose == undefined) {
            return;
        }
        if (this.timeclose == 0) {
            this.labelTimer.innerHTML = '';
            return;
        }
        let time = (new Date()).getTime();
        let dif = this.timeclose - time / 1000;

        if (dif <= 0) {
            dif = 0;
        }
        dif = Math.round(dif);
        if (dif <= 0) {
            this.labelTimer.innerHTML = '';
            this.onTimeout();
        } else {
            this.labelTimer.innerHTML = (dif < 0 ? "-" : "") + Math.floor(dif / 60.0) + ":" + ("0" + (dif % 60)).substr(-2);
        }

        if (dif <= 0 && this.timeclose != 0) {
            return;
        }

        let instance = this;
        this.timerTimeout = setTimeout(function() {
            instance.updateLabelTimer();
        }, 500);
    }

    onTimeout() {
        this.labelTimer.innerHTML = '';
        this.disableInput();
        this.sendTimeout();
    }

    createScreen(json, disabled) {
        if (this.area != undefined) {
            this.body.removeChild(this.area);
            this.area = undefined;
        }
        this.removeDivMessage();
        this.area = this.createDiv(this.body, this.padding, this.areaTop, this.areaWidth, this.areaHeight);

        if (this.endofgame) {
            this.createDivMessage('LANGM_GAME_OVER_' + this.errorcode);
            this.showScore(json);
            return;
        }

        if (this.vertical) {
            this.createScreenVertical(disabled);
        } else {
            this.createScreenHorizontal(disabled);
        }
        this.showScore(json);
    }

    createScreenVertical(disabled) {
        let nickNameHeight = Math.round(this.iconSize / 3) + this.padding;
        let maxHeight = this.areaHeight - 4 * this.padding - nickNameHeight;

        if (this.hideSubmit == false) {
            maxHeight -= this.iconSize;
        }
        let instance = this;
        let maxWidth = this.areaWidth;
        this.fontSize = this.findbest(this.minFontSize, this.maxFontSize, function(fontSize) {
                let defSize = instance.createDefinition(0, 0, maxWidth - 1, true, fontSize);

                if (defSize[0] >= maxWidth) {
                    return 1;
                }
                let ansSize = instance.createAnswer(0, 0, maxWidth - 1, true, fontSize, disabled);
                if (ansSize[0] >= maxWidth) {
                    return 1;
                }
                return defSize[1] + ansSize[1] < maxHeight ? -1 : 1;
            }
        );

        this.radioSize = Math.round(this.fontSize);
        this.explainLeft = 0;
        let defSize = this.createDefinition(0, 0, maxWidth, false, this.fontSize);

        this.nextTop = instance.createAnswer(0, defSize[1] + this.padding, maxWidth, false, this.fontSize, disabled);
        this.nextLeft = this.areaWidth - this.iconSize - this.padding;

        if (this.nextTop + this.padding >= this.areaHeight) {
            this.nextTop = this.areaHeight - this.padding;
        }

        if (this.hideSubmit == false) {
            this.btnSubmit = this.createImageButton(this.body, (this.areaWidth - this.iconSize) / 2, this.nextTop, 0, this.iconSize,
                "", 'assets/submit.svg', false, 'submit');
            this.btnSubmit.addEventListener("click",
                function() {
                    if (this.btnSubmit != undefined) {
                        instance.area.removeChild(this.btnSubmit);
                        this.btnSubmit = undefined;
                    }
                    instance.sendAnswer(true);
                }
            );
        }

        this.stripLeft = this.padding;
        this.stripTop = this.nextTop;
        this.stripWidth = 2 * this.iconSize;
        this.stripHeight = this.iconSize;
    }

    createScreenHorizontal(disabled) {
        let maxHeight = this.areaHeight - 2 * this.padding;

        if (this.hideSubmit == false) {
            maxHeight -= this.iconSize + this.padding;
        }
        let width = Math.round((this.areaWidth - this.padding) / 2);
        let instance = this;
        for (let step = 1; step <= 2; step++) {
            let defSize;
            this.fontSize = this.findbest(step == 1 ? this.minFontSize : this.minFontSize / 2, this.maxFontSize,
                function(fontSize) {
                    defSize = instance.createDefinition(0, 0, width - instance.padding, true, fontSize);

                    if (defSize[0] >= width) {
                        return 1;
                    }
                    let ansSize = instance.createAnswer(0, 0, width - instance.padding, true, fontSize, disabled);
                    if (ansSize[0] >= width) {
                        return 1;
                    }
                    return defSize[1] < maxHeight && ansSize[1] < maxHeight ? -1 : 1;
                }
            );
            if (defSize[0] <= width && defSize[1] <= instance.areaHeight) {
                break;
            }
        }

        this.radioSize = Math.round(this.fontSize);
        this.createDefinition(0, 0, width - this.padding, false, this.fontSize);

        this.nextTop = instance.createAnswer(width, 0, width - this.padding, false, this.fontSize, disabled) + this.padding;
        this.nextLeft = width + Math.min(3 * this.iconSize + 2 * this.padding, width - this.iconSize);

        if (this.hideSubmit) {
            return;
        }

        this.btnSubmit = this.createImageButton(this.body, width + (width - this.iconSize) / 2, this.nextTop, 0, this.iconSize,
            "", 'assets/submit.svg', false, 'submit');
        this.btnSubmit.addEventListener("click", function() {
            instance.sendAnswer(true);
        });

        this.stripLeft = width + this.padding;
        this.stripTop = this.nextTop;
        this.stripWidth = 2 * this.iconSize;
        this.stripHeight = this.iconSize;
    }

    createAnswer(left, top, width, onlyMetrics, fontSize, disabled) {
        return this.createAnswerMultichoice(left, top, width, onlyMetrics, fontSize, disabled);
    }

    createAnswerMultichoice(left, top, width, onlyMetrics, fontSize, disabled) {
        let n = this.answers.length;
        let instance = this;

        let aChecked = [];
        if (this.answer != undefined) {
            aChecked = this.answer.split(",");
        }
        if (aChecked.length > 0) {
            if (aChecked[0] == "") {
                aChecked.pop();
            }
        }

        this.multichoiceLeft = left;
        this.aItemAnswer = new Array(n);
        this.aItemLabel = new Array(n);
        this.aItemCorrectX = new Array(n);
        let retSize = [0, 0];
        this.labelWidth = Math.round(width - fontSize - this.padding - this.getMultichoiceSpace(fontSize));
        let checkboxSize = Math.round(fontSize);
        let top1 = top;
        let offsetLabel = this.getMultichoiceSpace(fontSize);
        for (let i = 0; i < n; i++) {
            var label = document.createElement("label");
            label.style.position = "absolute";
            label.style.width = this.labelWidth + "px";

            label.innerHTML = this.repairHTML(this.answers[i], this.mapFiles, this.mapFilesWidth, this.mapFilesHeight);

            label.style.font = "FontAwesome";
            label.style.fontSize = fontSize + "px";
            this.aItemLabel[i] = label;

            if (onlyMetrics) {
                this.body.appendChild(label);
                let newSize = label.scrollWidth + fontSize + this.padding + this.getMultichoiceSpace(fontSize);
                if (newSize > retSize[0]) {
                    retSize[0] = newSize;
                }
                retSize[1] += Math.max(label.scrollHeight, fontSize) + this.padding;

                this.body.removeChild(label);
                continue;
            }

            label.htmlFor = "input" + i;
            label.style.left = (left + fontSize + this.padding + offsetLabel) + "px";
            label.style.top = top + "px";
            label.style.align = "left";
            label.style.color = this.getColorContrast(this.colorBackground);

            let checked = aChecked.includes(this.answersID[i]);
            let item = this.createRadiobox(this.body, checkboxSize, this.colorDefinition, this.colorScore, checked, disabled);
            item.style.position = "absolute";
            item.style.left = left + "px";
            let topRadio = top;
            item.style.top = topRadio + "px";

            item.addEventListener('click', (event) => {
                if (!item.classList.contains("disabled")) {
                    instance.onClickRadio(i, this.colorDefinition, this.colorScore, true);
                }
            });

            new FastButton(label, function() {
                instance.onClickRadio(i, instance.colorDefinition, instance.colorScore, true);
            });

            this.area.appendChild(item);
            this.area.appendChild(label);
            if (label.scrollHeight > fontSize) {
                topRadio = Math.round(top + (label.scrollHeight - fontSize) / 2);
                item.style.top = topRadio + "px";
            }

            if (this.answersID[i] == '') {
                item.style.visibility = 'hidden';
                label.style.visibility = 'hidden';
            }

            this.aItemAnswer[i] = item;
            this.aItemCorrectX[i] = left + fontSize + this.padding;

            top += Math.max(label.scrollHeight, fontSize) + this.padding;
        }

        if (onlyMetrics) {
            return retSize;
        }

        let heightControls = top - top1;
        let vspace;
        if (this.vertical == false) {
            vspace = this.areaHeight - heightControls - this.iconSize;
            if (vspace > this.padding) {
                let move = Math.round(vspace / 3);
                for (let i = 0; i < n; i++) {
                    this.aItemAnswer[i].style.top = (parseInt(this.aItemAnswer[i].style.top) + move) + "px";
                    this.aItemLabel[i].style.top = (parseInt(this.aItemLabel[i].style.top) + move) + "px";
                }
                this.nextTop += move / 2;
                let defTop = parseInt(this.divDefinition.style.top);
                if (defTop + move + this.definitionHeight + this.padding < this.areaHeight) {
                    this.divDefinition.style.top = this.aItemLabel[0].style.top;
                    this.divDefinition.style.height = Math.max(heightControls, this.definitionHeight) + "px";
                }
                top += move;
            }
        }

        return top;
    }

    onClickRadio(i, colorBack, color, callSendAnswer) {
        if (this.aItemAnswer[i].classList.contains("disabled")) {
            return;
        }

        for (let j = 0; j < this.aItemAnswer.length; j++) {
            let item = this.aItemAnswer[j];
            let disabled = item.classList.contains("disabled");

            if (i == j) {
                item.classList.add("checked");
                this.answer = this.answersID[i];
            } else if (item.classList.contains("checked")) {
                item.classList.remove("checked");
            }

            this.drawRadio(item, disabled ? colorBack : 0xFFFFFF, color);
        }
        if (this.autosave && callSendAnswer) {
            this.sendAnswer(this.autosubmit);
        }
    }

    getMultichoiceSpace(fontSize) {
        return 0;
    }

    sendAnswer(submit, subcommand) {
        if (submit) {
            clearTimeout(this.timerTimeout);
            this.timerTimeout = undefined;
        }

        var xmlhttp = new XMLHttpRequest();
        var instance = this;
        xmlhttp.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {
                let json = JSON.parse(this.responseText);
                if (submit) {
                    json.submit = 1;
                }
                instance.onServerAnswer(json, false);
            }
        };
        xmlhttp.open("POST", this.url, true);

        xmlhttp.setRequestHeader("Content-Type", "application/json");
        let a = {"command": "answer", "mmogameid": this.mmogameid, "pin": this.pin, 'kinduser': this.kinduser,
            "user": this.auserid, "attempt": this.attempt, "answer": this.answer, 'submit': submit ? 1 : 0};
        if (subcommand != undefined) {
            a['subcommand'] = subcommand;
        }
        let data = JSON.stringify(a);
        xmlhttp.send(data);
    }

    sendTimeout(submit) {
        var xmlhttp = new XMLHttpRequest();
        var instance = this;
        xmlhttp.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {
                instance.sendGetAttempt();
            }
        };
        xmlhttp.open("POST", this.url, true);

        xmlhttp.setRequestHeader("Content-Type", "application/json");
        var data = JSON.stringify({"command": "timeout", "mmogameid": this.mmogameid, "pin": this.pin, 'kinduser': this.kinduser,
            "user": this.auserid, "attempt": this.attempt});
        xmlhttp.send(data);
    }

    onServerAnswer(json, timeout) {
        if (json.submit == 0) {
            return;
        }
        this.correct = json.correct;

        this.playAudio(json.iscorrect != 0 ? this.audioYes : this.audioNo);

        if (json.correct != undefined) {
            if (this.qtype == "multichoice") {
                this.onServerAnswerMultichoice(json);
            }
        }
        this.disableInput();

        if (this.btnSubmit != undefined) {
            this.body.removeChild(this.btnSubmit);
            this.btnSubmit = undefined;
        }

        let btn = super.createImageButton(this.area, this.nextLeft, this.nextTop, 0, this.iconSize, "", 'assets/next.svg', false,
            'alt');
        let instance = this;
        btn.addEventListener("click", function() {
            instance.sendGetAttempt();
            instance.area.removeChild(btn);       
        });

        if (!json.iscorrect && this.qtype != "multichoice") {
            let w = this.nextLeft - this.explainLeft - 2 * this.padding - this.iconSize;
            let ans2 = this.createDiv(this.body, this.explainLeft + this.iconSize + this.padding, this.nextTop, this.iconSize,
                this.iconSize);
            ans2.style.lineHeight = this.iconSize + "px";
            ans2.style.color = this.getColorContrast(this.colorBackground);
            ans2.innerHTML = json.correct;
            this.autoResizeText(ans2, w, this.iconSize, false, this.minFontSize, this.maxFontSize, 0.9);
        }

        this.showScore(json);
    }

    getSVGcorrect(size, iscorrect, colorCorrect, colorError) {
        if (iscorrect) {
            let c = colorCorrect != undefined ? this.getColorHex(colorCorrect) : '#398439';
            return "<svg aria-hidden=\"true\" class=\"svg-icon iconCheckmarkLg\" width=\"" + size + "\" height=\"" + size +
                "\" viewBox=\"0 0 36 36\"><path fill=\"" + c + "\" d=\"m6 14 8 8L30 6v8L14 30l-8-8v-8z\"></path></svg>";
        } else {
            let c = colorError != undefined ? this.getColorHex(colorError) : '#398439';
            return "<svg width=\"" + size + "\" height=\"" + size +
                "\" class=\"bi bi-x-lg\" viewBox=\"0 0 18 18\"> <path fill=\"" + c +
                `" d="M1.293 1.293a1 1 0 0 1 1.414 0L8 6.586l5.293-5.293a1 1 0 1 1 1.414 1.414L9.414 8l5.293 5.293a1 1 0 0 
                1-1.414 1.414L8 9.414l-5.293 5.293a1 1 0 0 1-1.414-1.414L6.586 8 1.293 2.707a1 1 0 0 1 0-1.414z"/></svg>`;
        }
    }

    onServerAnswerMultichoice(json) {
        let aCorrect = json.correct.split(",");

        for (let i = 0; i < this.answersID.length; i++) {
            let label = this.aItemLabel[i];
            let checked = this.aItemAnswer[i].classList.contains("checked");
            let iscorrect;

            if (aCorrect.includes(this.answersID[i])) {
                iscorrect = true;
            } else if (json.correct != undefined && checked) {
                iscorrect = false;
            } else {
                continue;
            }
            let height = label.scrollHeight;
            let width = label.scrollWidth - this.radioSize;
            label.style.left = (parseInt(label.style.left) + this.radioSize) + "px";
            label.style.width = width + "px";
            if (iscorrect) {
                label.innerHTML = '<b><u>' + label.innerHTML + '</u></b>';
            }
            this.autoResizeText(label, width, height, true, this.minFontSize, this.maxFontSize, 0.9);

            let t = parseInt(this.aItemAnswer[i].style.top);
            let div = this.createDiv(this.area, this.aItemCorrectX[i], t, this.radioSize, this.radioSize);
            div.innerHTML = this.getSVGcorrect(this.radioSize, iscorrect, this.colorScore, this.colorScore);
        }
    }

    disableInput() {
        if (this.aItemAnswer != undefined) {
            for (let i = 0; i < this.aItemAnswer.length; i++) {
                this.aItemAnswer[i].classList.add("disabled");
                this.drawRadio(this.aItemAnswer[i], this.colorScore, this.colorDefinition);
            }
        }
    }

    sendFastJSON() {
        if (this.timeoutFastJSON != undefined) {
            clearTimeout(this.timeoutFastJSON);
        }
        let instance = this;
        this.timeoutFastJSON = setTimeout(function() {
            var xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function() {
                this.timeoutFastJSON = undefined;
                if (this.readyState == 4 && this.status == 200) {
                    instance.onServerFastJson(this.response);
                }
            };

            let url = instance.url.substr(0, instance.url.length - 8) + "state.php";

            xmlhttp.open("POST", url, true);

            let data = new FormData();
            data.set('fastjson', instance.fastjson);
            data.set('type', instance.type);
            data.set('dataroot', instance.dataroot);

            xmlhttp.send(data);
        }, 1000);
    }

    onClickHelp() {
        if (this.helpUrl != '') {
            window.open(this.helpUrl, "_blank");
        }
    }
}

const stateAlonePlay = 1;
const stateAloneLast = 1;

class mmogameQuizAlone extends mmogameQuiz {
    constructor() {
        super();
        this.cIcons = this.hasHelp() ? 5 : 4;
        this.autosave = true;
        this.autosubmit = true;
        this.type = 'alone';
    }

    createIconBar() {
        let i = 0;

        this.nickNameHeight = Math.round(this.iconSize / 3);

        this.buttonAvatarLeft = this.padding + i * (this.iconSize + this.padding);
        this.buttonAvatarHeight = Math.round(this.iconSize - this.iconSize / 3);
        this.buttonAvatarTop = this.iconSize - this.buttonAvatarHeight + this.padding;
        this.createButtonsAvatar(1, Math.round(this.padding + (i++) * (this.iconSize + this.padding)));
        this.buttonsAvatar[1].style.top = (this.padding + this.nickNameHeight) + "px";

        this.divNickname = this.createDiv(this.body, this.buttonAvatarLeft, this.padding, this.iconSize, this.buttonAvatarTop);

        this.createDivScorePercent(this.padding + (i++) * (this.iconSize + this.padding), this.padding + this.nickNameHeight, 1);

        this.createButtonSound(this.padding + (i++) * (this.iconSize + this.padding), this.padding + this.nickNameHeight);
        let instance = this;
        if (this.hasHelp()) {
            this.createButtonHelp(this.padding + (i++) * (this.iconSize + this.padding), this.padding);
            this.buttonHelp.addEventListener("click", function() {
                instance.onClickHelp(this);
            });
        }

        let copyrightHeight = this.getCopyrightHeight();

        this.areaTop = 2 * this.padding + this.iconSize + this.nickNameHeight;
        this.areaWidth = Math.round(window.innerWidth - 2 * this.padding);

        this.areaHeight = Math.round(window.innerHeight - this.areaTop - copyrightHeight);

        this.createDivColor(this.body, 0, window.innerHeight - copyrightHeight - 1, window.innerWidth - 1, copyrightHeight,
            this.getColorGray(this.colorCopyright));
        this.vertical = this.areaHeight > this.areaWidth;

        this.maxImageWidth = (this.vertical ? this.areaWidth : this.areaWidth / 2);
        this.maxImageHeight = (this.vertical ? this.areaHeight / 2 : this.areaWidth);
    }

    createDivPercent(left, top, num, oneLine) {
        var button = document.createElement("button");
        button.classList.add("mmogame_button");
        button.style.left = left + "px";
        button.style.top = top + "px";
        button.style.width = this.iconSize + "px";
        button.style.height = this.iconSize + "px";
        button.style.lineHeight = this.iconSize + "px";
        button.style.textAlign = "center";
        button.style.borderRadius = this.iconSize + "px";
        button.style.border = "0px solid " + this.getColorHex(0xFFFFFF);
        button.style.boxShadow = "inset 0 0 0.125em rgba(255, 255, 255, 0.75)";
        button.title = "[LANGM_PERCENT]";
        button.alt = "[LANGM_PERCENT]";
        this.buttonPercent = button;
        button.style.background = this.getColorHex(this.colorScore2);
        button.style.color = this.getColorContrast(this.colorScore2);

        this.body.appendChild(button);

        button.innerHTML = '-100 %-';
        button.style.whiteSpace = "nowrap";
        this.autoResizeText(button, this.iconSize, this.iconSize, false, 0, 0, 1);
        button.innerHTML = '';

        let h = this.iconSize / 2;
        let div = this.createDiv(this.body, left, top + this.iconSize / 4, this.iconSize, this.iconSize / 2);
        div.style.textAlign = "center";
        div.style.color = this.getColorContrast(this.colorScore2);
        this.labelPercent = div;

        h = this.iconSize / 3;
        div = this.createDiv(this.body, left, top, this.iconSize, h);
        div.style.textAlign = "center";
        div.style.lineHeight = h + "px";
        div.style.color = this.getColorContrast(this.colorScore2);
        button.disabled = true;
        this.labelPercentRank = div;
    }

    updatePercent(json) {
        if (this.labelPercent != undefined) {
            let s = json.percentcompleted == undefined ? '' : '<b>' + Math.round(100 * json.percentcompleted) + ' %</b>';
            if (this.labelPercent.innerHTML != s) {
                this.labelPercent.innerHTML = s;
                this.autoResizeText(this.labelPercent, this.iconSize - 2 * this.padding, this.iconSize / 2, false, 0, 0, 1);
            }
        }
    }

    onServerAnswer(json, timeout) {
        super.onServerAnswer(json, timeout);

        if (json.submit != 0) {
            this.updatePercent(json);
        }
    }

    onServerFastJson(response) {
        let pos = response.indexOf("-");
        if (pos >= 0) {
            let state = parseInt(response.substr(0, pos));
            let timefastjson = response.substr(pos + 1);
            if (timefastjson != this.timefastjson || state != this.state) {
                this.sendGetAttempt();
                return;
            }
        }

        this.sendFastJSON();
    }

    onServerGetAttempt(json, param) {
        if (json.dataroot != undefined) {
            this.dataroot = json.dataroot;
        }

        if (json.state == 0 && param == undefined) {
            json.qtype = '';
            super.onServerGetAttempt(json, param);
            this.showScore(json);

            this.createDivMessageStart('[LANGM_WAIT_TO_START]');
            return;
        }

        super.onServerGetAttempt(json, param);
        this.updatePercent(json);
        if (this.btnSubmit != undefined) {
            this.btnSubmit.style.visibility = "hidden";
        }
    }

    showScore(json) {
        let rank = json.rank;
        let rankc = json.completedrank;
        if (rank != undefined && rankc != undefined) {
            if (parseInt(rank) < parseInt(rankc)) {
                json.completedrank = '';
                json.rank = '# ' + rank;
            } else {
                json.rank = '';
                json.completedrank = '# ' + rankc;
            }
        }

        let s = json.sumscore;
        json.sumscore = this.labelScore.innerHTML;
        super.showScore(json);
        json.sumscore = s;
        s = json.sumscore == undefined ? '' : '<b>' + json.sumscore + '</b>';
        if (this.labelScore.innerHTML != s) {
            this.labelScore.innerHTML = s;
            this.autoResizeText(this.labelScore, 0.8 * this.iconSize / 2, this.iconSize / 2, false, 0, 0, 1);
        }

        json.rank = rank;
        json.completedrank = rankc;
    }

    showHelpScreen(div, width, height) {
        div.innerHTML = `<br>
<div>[LANG_ALONE_HELP]</div><br>

<table border=1>
    <tr>
        <td><img height="90" src="type/quiz/assets/aduel/example2.png" alt="" /></td>
        <td>[LANG_ADUEL_EXAMPLE2]</td>
    </tr>
</table>
        `;
    }
}
