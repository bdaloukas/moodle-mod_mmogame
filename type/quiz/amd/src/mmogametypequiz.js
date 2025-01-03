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
    return class MmoGameTypeQuiz extends MmoGame {
        mmogameid;
        kinduser;
        auserid;
        url;
        pin;
        labelTimer;

        /**
         * Base class for Quiz mmmogame
         *
         * @module mmogametype_quiz
         * @copyright 2024 Vasilis Daloukas
         * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
         */

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

        openGame() {
            super.openGame();

            this.audioYes = new Audio('assets/yes1.mp3');
            this.audioYes.load();
            this.audioNo = new Audio('assets/no1.mp3');
            this.audioNo.load();
        }
/* A
        sendGetAttempt(param, subcommand) {
            let xmlhttp = new XMLHttpRequest();
            let instance = this;
            xmlhttp.onreadystatechange = function() {
                if (this.readyState === 4 && this.status === 200) {
                    instance.onServerGetAttempt(JSON.parse(this.responseText), param);
                }
            };

            xmlhttp.open("POST", this.url, true);
            xmlhttp.setRequestHeader("Content-Type", "application/json");
            let d = {
                "command": "getattempt", "mmogameid": this.mmogameid, "pin": this.pin, "kinduser": this.kinduser,
                "user": this.auserid, "subcommand": subcommand
            };
            if (this.helpurl === undefined) {
                d.helpurl = 1;
            }
            let data = JSON.stringify(d);
            xmlhttp.send(data);
        }
*/

        onServerGetAttempt(json) {
            this.computeDifClock(json.time, json.timestart, json.timeclose);

            if (json.colors !== undefined) {
                this.setColorsString(json.colors);
                this.createIconBar();
            }

            if (name !== undefined) {
                window.document.title = name;
            }

            if (json.helpurl !== undefined) {
                this.helpUrl = json.helpurl;
            }

            if (json.errorcode !== undefined) {
                this.createDivMessage(json.errorcode);
                return;
            }

            this.state = parseInt(json.state);
            this.fastjson = json.fastjson;
            this.timefastjson = parseInt(json.timefastjson);
            this.updateButtonsAvatar(1, json.avatar, json.nickname);

            this.attempt = json.attempt;

            this.qtype = json.qtype;
            if (json.qtype === 'multichoice') {
                this.answers = json.answers;
                this.answersID = json.answerids;
            }
            this.answer = json.answer !== undefined ? json.answer : null;
            this.endofgame = json.endofgame !== undefined && json.endofgame !== 0;
            this.definition = this.repairP(json.definition);
            this.errorcode = json.errorcode;

            if (json.state !== 0) {
                this.createScreen(json.sumscore, json.rank, json.name, '', '', '',
                    json.percentcompleted, false);
            }

            this.updateLabelTimer();

            this.sendFastJSON();
        }

        updateLabelTimer() {
            if (this.labelTimer === undefined || this.timeclose === undefined) {
                return;
            }
            if (this.timeclose === 0) {
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
                let s = (dif < 0 ? "-" : "") + Math.floor(dif / 60.0);
                this.labelTimer.innerHTML = s + ":" + ("0" + (dif % 60)).slice(-2);
            }

            if (dif <= 0 && this.timeclose !== 0) {
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

        createScreen(sumscore, rank, name, usercode, addscore, completerank, percentcompleted, disabled) {
            if (this.area !== undefined) {
                this.body.removeChild(this.area);
                this.area = undefined;
            }
            this.removeDivMessage();
            this.area = this.createDiv(this.body, this.padding, this.areaTop, this.areaWidth, this.areaHeight);

            if (this.endofgame) {
                this.createDivMessage(this.getStringM('js_game_over'));
                this.showScore(sumscore, rank, name, usercode, addscore, completerank, percentcompleted);
                return;
            }

            if (this.vertical) {
                this.createScreenVertical(disabled);
            } else {
                this.createScreenHorizontal(disabled);
            }
            this.showScore(sumscore, rank, name, usercode, addscore, completerank, percentcompleted);
        }

        createScreenVertical(disabled) {
            let nickNameHeight = Math.round(this.iconSize / 3) + this.padding;
            let maxHeight = this.areaHeight - 4 * this.padding - nickNameHeight;

            if (this.hideSubmit === false) {
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
            let defSize = this.createDefinition(0, 0, maxWidth, false, this.fontSize);

            this.nextTop = instance.createAnswer(0, defSize[1] + this.padding, maxWidth, false, this.fontSize, disabled);
            this.nextLeft = this.areaWidth - this.iconSize - this.padding;

            if (this.nextTop + this.padding >= this.areaHeight) {
                this.nextTop = this.areaHeight - this.padding;
            }

            if (this.hideSubmit === false) {
                let top = (this.areaWidth - this.iconSize) / 2;
                this.btnSubmit = this.createImageButton(this.body, top, this.nextTop, 0, this.iconSize,
                    "", 'assets/submit.svg', false, 'submit');
                this.btnSubmit.addEventListener("click",
                    function() {
                        if (instance.btnSubmit !== undefined) {
                            instance.area.removeChild(instance.btnSubmit);
                            instance.btnSubmit = undefined;
                        }
                        instance.sendAnswer();
                    }
                );
            }

            this.stripLeft = this.padding;
            this.stripWidth = 2 * this.iconSize;
            this.stripHeight = this.iconSize;
        }

        createScreenHorizontal(disabled) {
            let maxHeight = this.areaHeight - 2 * this.padding;

            if (this.hideSubmit === false) {
                maxHeight -= this.iconSize + this.padding;
            }
            let width = Math.round((this.areaWidth - this.padding) / 2);
            let instance = this;
            for (let step = 1; step <= 2; step++) {
                let defSize;
                this.fontSize = this.findbest(step === 1 ? this.minFontSize : this.minFontSize / 2, this.maxFontSize,
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
                instance.sendAnswer();
            });

            this.stripLeft = width + this.padding;
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
            if (this.answer !== null) {
                aChecked = this.answer.split(",");
            }
            if (aChecked.length > 0) {
                if (aChecked[0] === "") {
                    aChecked.pop();
                }
            }

            this.aItemAnswer = new Array(n);
            this.aItemLabel = new Array(n);
            this.aItemCorrectX = new Array(n);
            let retSize = [0, 0];
            this.labelWidth = Math.round(width - fontSize - this.padding);
            let checkboxSize = Math.round(fontSize);
            let top1 = top;
            let offsetLabel = 0;
            for (let i = 0; i < n; i++) {
                let label = document.createElement("label");
                label.style.position = "absolute";
                label.style.width = this.labelWidth + "px";

                label.innerHTML = this.answers[i];

                label.style.font = "FontAwesome";
                label.style.fontSize = fontSize + "px";
                this.aItemLabel[i] = label;

                if (onlyMetrics) {
                    this.body.appendChild(label);
                    let newSize = label.scrollWidth + fontSize + this.padding;
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

                item.addEventListener('click', () => {
                    if (!item.classList.contains("disabled")) {
                        instance.onClickRadio(i, this.colorDefinition, this.colorScore, true);
                    }
                });

                label.addEventListener('click', () => {
                    instance.onClickRadio(i, instance.colorDefinition, instance.colorScore, true);
                });

                this.area.appendChild(item);
                this.area.appendChild(label);
                if (label.scrollHeight > fontSize) {
                    topRadio = Math.round(top + (label.scrollHeight - fontSize) / 2);
                    item.style.top = topRadio + "px";
                }

                if (this.answersID[i] === '') {
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
            if (this.vertical === false) {
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

                if (i === j) {
                    item.classList.add("checked");
                    this.answer = this.answers[i];
                    this.answerid = this.answersID[i];
                } else if (item.classList.contains("checked")) {
                    item.classList.remove("checked");
                }

                this.drawRadio(item, disabled ? colorBack : 0xFFFFFF, color);
            }
            if (this.autosave && callSendAnswer) {
                this.sendAnswer();
            }
        }

        sendTimeout() {
            let xmlhttp = new XMLHttpRequest();
            let instance = this;
            xmlhttp.onreadystatechange = function() {
                if (this.readyState === 4 && this.status === 200) {
                    instance.sendGetAttempt();
                }
            };
            xmlhttp.open("POST", this.url, true);

            xmlhttp.setRequestHeader("Content-Type", "application/json");
            let data = JSON.stringify({
                "command": "timeout", "mmogameid": this.mmogameid, "pin": this.pin, 'kinduser': this.kinduser,
                "user": this.auserid, "attempt": this.attempt
            });
            xmlhttp.send(data);
        }

        updateScreenAfterAnswer(correct, iscorrect) {
            this.playAudio(iscorrect !== 0 ? this.audioYes : this.audioNo);

            if (correct !== undefined) {
                if (this.qtype === "multichoice") {
                    this.onServerAnswerMultichoice();
                }
            }
            this.disableInput();

            if (this.btnSubmit !== undefined) {
                this.body.removeChild(this.btnSubmit);
                this.btnSubmit = undefined;
            }

            let btn = super.createImageButton(this.area, this.nextLeft, this.nextTop, 0, this.iconSize, "", 'assets/next.svg',
                false, 'alt');
            let instance = this;
            btn.addEventListener("click", function() {
                instance.sendGetAttempt();
                instance.area.removeChild(btn);
            });

            this.showScore();
        }

        getSVGcorrect(size, iscorrect, colorCorrect, colorError) {
            if (iscorrect) {
                let c = colorCorrect !== undefined ? this.getColorHex(colorCorrect) : '#398439';
                return "<svg aria-hidden=\"true\" class=\"svg-icon iconCheckmarkLg\" width=\"" + size + "\" height=\"" + size +
                    "\" viewBox=\"0 0 36 36\"><path fill=\"" + c + "\" d=\"m6 14 8 8L30 6v8L14 30l-8-8v-8z\"></path></svg>";
            } else {
                let c = colorError !== undefined ? this.getColorHex(colorError) : '#398439';
                return "<svg width=\"" + size + "\" height=\"" + size +
                    "\" class=\"bi bi-x-lg\" viewBox=\"0 0 18 18\"> <path fill=\"" + c +
                    `" d="M1.293 1.293a1 1 0 0 1 1.414 0L8 6.586l5.293-5.293a1 1 0 1 1 1.414 1.414L9.414 8l5.293 5.293a1 1 0 0 
                1-1.414 1.414L8 9.414l-5.293 5.293a1 1 0 0 1-1.414-1.414L6.586 8 1.293 2.707a1 1 0 0 1 0-1.414z"/></svg>`;
            }
        }

        onServerAnswerMultichoice() {
            let aCorrect = this.correct.split(",");
            for (let i = 0; i < this.answersID.length; i++) {
                let label = this.aItemLabel[i];
                let checked = this.aItemAnswer[i].classList.contains("checked");
                let iscorrect;

                if (aCorrect.includes(this.answersID[i])) {
                    iscorrect = true;
                } else if (checked) {
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
            if (this.aItemAnswer !== undefined) {
                for (let i = 0; i < this.aItemAnswer.length; i++) {
                    this.aItemAnswer[i].classList.add("disabled");
                    this.drawRadio(this.aItemAnswer[i], this.colorScore, this.colorDefinition);
                }
            }
        }

        sendFastJSON() {
            if (this.timeoutFastJSON !== undefined) {
                clearTimeout(this.timeoutFastJSON);
            }
            let instance = this;
            this.timeoutFastJSON = setTimeout(function() {
                let xmlhttp = new XMLHttpRequest();
                xmlhttp.onreadystatechange = function() {
                    this.timeoutFastJSON = undefined;
                    if (this.readyState === 4 && this.status === 200) {
                        instance.onServerFastJson(this.response);
                    }
                };

                let url = instance.url + "/state.php";

                xmlhttp.open("POST", url, true);

                let data = new FormData();
                data.set('fastjson', instance.fastjson);
                data.set('type', instance.type);

                xmlhttp.send(data);
            }, 1000);
        }

        onClickHelp() {
            if (this.helpUrl !== '') {
                window.open(this.helpUrl, "_blank");
            }
        }

        getStringG(name) {
            return M.util.get_string(name, 'mmogametype_quiz');
        }
    };
    });