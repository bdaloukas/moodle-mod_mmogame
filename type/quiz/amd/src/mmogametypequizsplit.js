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

define(['mod_mmogame/mmogamesplit'], function(MmoGameSplit) {
    return class MmoGameQuizSplit extends MmoGameSplit {

        splits;
        lastUpdateTime;
        durationAnination = 500;
        timeBeforeShowNextButtonCorrect = 500;
        timeBeforeShowNextButtonError = 3000;

        gateOpen(mmogameid, pin, kinduser, user, modelparams) {
            while (this.body.firstChild) {
                this.body.removeChild(this.body.firstChild);
            }

            let splitx = 1;
            let splity = 1;
            let isaduel = 0;

            if (modelparams !== null) {
                const params = JSON.parse(modelparams);
                if (params.splitx !== undefined && params.splitx > 1) {
                    splitx = params.splitx;
                }
                if (params.splity !== undefined && params.splity > 1) {
                    splity = params.splity;
                }
                if (params.isaduel !== undefined && params.isaduel > 1) {
                    isaduel = params.isaduel;
                }
            }
            this.countX = splitx;
            this.countY = splity;

            this.countAll = this.countX * this.countY;
            this.isaduel = (isaduel !== 0);
            this.computeSizes(0);

            super.gateOpen(mmogameid, pin, kinduser, user, splitx, splity);

            window.addEventListener("gamepadconnected", () => {
                requestAnimationFrame(() => this.updateGamepads());
            });
        }

        play() {
            this.screen = 1;
            this.updateDelay = 500;
            this.computeSizes(0);
            this.callGetAttemptsSplit();
        }

        callGetAttemptsSplit() {
            let avatarids = [];
            const info = this.info;
            this.avatarfiles = [];
            let splits = '';
            for (let i = 0; i < this.splits.length; i++) {
                const sp = this.splits[i];
                avatarids.push(sp.avatarid);
                this.avatarfiles.push(info.avatars[this.gategetavatar(i, sp.avatarpos)]);
                if (i > 0) {
                    splits += ",";
                }
                splits += i;
            }

            require(['core/ajax'], (Ajax) => {
                // Defining the parameters to be passed to the service
                let params = {
                    mmogameid: this.mmogameid,
                    kinduser: this.kinduser,
                    user: this.user,
                    splits: splits,
                    avatarids: avatarids.join(','),
                };
                // Calling the service through the Moodle AJAX API
                let getAttemptsSplit = Ajax.call([{
                    methodname: 'mmogametype_quiz_get_attempts_split',
                    args: params
                }]);

                // Handling the response
                getAttemptsSplit[0].done(({avatars, attempts, attemptqueryids, querydefinitions, querytips,
                                              queryanswerids, numattempts, answertexts, aduels,
                                              aduelavatars, aduelcorrects, auserids, queryanswerids0, grades,
                                              countquestion, countcorrect, islastcorrect, ranks, queryranks,
                                              hasidea, state, statetime}) => {
                    this.info = {
                        avatars: avatars,
                        attempts: attempts,
                        paletteid: this.paletteid,
                        attemptqueryids: attemptqueryids,
                        numattempts: numattempts,
                        querydefinitions: querydefinitions,
                        querytips: querytips,
                        queryanswerids: queryanswerids,
                        answertexts: answertexts,
                        aduels: aduels,
                        aduelavatars: aduelavatars,
                        aduelcorrects: aduelcorrects,
                        auserids: auserids,
                        queryanswerids0: queryanswerids0,
                        grades: grades,
                        countquestion: countquestion,
                        countcorrect: countcorrect,
                        islastcorrect: islastcorrect,
                        ranks: ranks,
                        queryranks: queryranks,
                        hasidea: hasidea,
                        state: state,
                        statetime: statetime,
                    };
                    if (this.palette !== undefined) {
                        this.setColors(this.palette);
                    }
                    if (this.info.state === 0) {
                        this.createDivMessageStart(this.getStringM('js_wait_to_start'));
                        setTimeout(() => {
                            this.callGetAttemptsSplit();
                        }, 10000);
                        return;
                    }
                    if (this.info.statetime !== 0) {
                        setTimeout(() => {
                            this.sendGetAttemptsSplit();
                        }, 1000 * this.info.statetime);
                    }
                    this.computeSizes(0, this.getMinIconSize(this.info.countquestion));
                    this.createScreen();
                }).fail((error) => {
                    return error;
                });
            });
        }

        createScreen() {
            this.screen = 1;
            this.split.width = Math.round(document.documentElement.clientWidth / this.countX) - this.padding;
            while (this.body.firstChild) {
                this.body.removeChild(this.body.firstChild);
            }

            const info = this.info;
            this.splits = [];
            for (let i = 0; i < this.countX * this.countY; i++) {
                if (i >= this.countAll) {
                    break;
                }
                const split = {parent: undefined};
                split.achecked = [];
                split.answers = info.answertexts;
                split.answerids = info.queryanswerids;
                split.avatarfile = info.avatars[i];
                split.lastUpdateTime = 0;
                split.score = 0;
                split.rank = 0;
                split.rankcache = '';
                split.isWaitingContinue = false;
                split.position = this.splits.length;
                split.server = [];
                this.splits.push(split);
            }
            for (let i = 0; i < this.countAll; i++) {
                this.copySplitData(i, i);
            }

            let i = 0;
            for (let iY = 0; iY < this.countY; iY++) {
                for (let iX = 0; iX < this.countX; iX++) {
                    if (i >= this.countAll) {
                        break;
                    }
                    this.createScreenSplit(i, iX, iY);
                    const sp = this.splits[i];
                    this.updateScore(sp);
                    this.updatePercent(sp);
                    this.checkShowAduel(sp);
                    i++;
                }
            }

            this.createScreenKeyboard();
        }

        createScreenSplit(split, iX, iY) {
            const sp = this.splits[split];
            const left = iX * (this.split.width + this.padding);
            const top = this.split.offsetY + iY * (this.split.height + this.padding);
            sp.parent = this.createDOMElement('div', {
                parent: this.body,
                classnames: 'mmogame-quiz-split',
                styles: {
                    position: 'absolute',
                    left: `${left}px`,
                    top: `${top}px`,
                    width: `${this.split.width}px`,
                    height: `${this.split.height}px`,
                    overflow: 'hidden',
                }
            });

            const ishorizontal = this.split.width >= this.split.height;
            // Const showRank = this.isaduel || this.countX * this.countY > 1;
            // const showRank = true;
            sp.player = this.createDivScorePercent(sp.parent, this.iconSize + 2 * this.padding, true);
            sp.percent = this.createDivScorePercent(sp.parent, 2 * this.iconSize + 3 * this.padding, false);
            this.computeFontScorePercent(sp);

            sp.ring = this.createDifficultyRing(sp.parent, 2 * this.iconSize + 3 * this.padding, this.padding, this.iconSize);
            if (this.info.countquestion > 0) {
                let percent = sp.attempts[0].queryrank / this.info.countquestion;
                this.updateDifficultyRing(sp.ring, percent);
            }
            // Avatar.
            const leftAvatar = Math.round(this.padding + this.iconSize / 2);
            sp.avatar = this.createDOMElement('img', {
                classname: `mmogame-quiz-avatar`,
                parent: sp.parent,
                styles: {
                    position: 'absolute',
                    left: `${leftAvatar}px`,
                    top: `${this.padding}px`,
                    height: `${this.iconSize}px`,
                    maxWidth: `${this.iconSize}px`,
                    transform: 'translateX(-50%)',
                },
                attributes: {
                    src: 'assets/avatars/' + sp.avatarfile,
                    alt: this.getStringM('js_help'),
                    role: 'button',
                },
            });

            sp.wizard = this.createDivWizard(sp, 3 * this.iconSize + this.iconSize / 2 + 4 * this.padding);
            sp.wizard.addEventListener("click", () => {
                this.createQuestionTip(sp);
            });

            sp.skip = this.createDivSkip(sp, 4 * this.iconSize + this.iconSize / 2 + 5 * this.padding);
            sp.skip.addEventListener("click", () => {
                this.sendAnswer(split, true, false, true);
                this.updateRanks();
                // Remove the question that is used.
                sp.attempts.shift();
                this.showNextQuestion(sp.position);
            });
            if (this.info.hasidea) {
                sp.idea = this.createDivIdea(sp, 5 * this.iconSize + this.iconSize / 2 + 6 * this.padding);
                sp.ideaCallback = () => {
                    this.sendAnswer(split, true, false, false, true);
                    this.askNextQuestions();
                };
                sp.idea.addEventListener("click", sp.ideaCallback);
            }

            // Definition.
            sp.definitionWidth = ishorizontal ?
                Math.round(this.split.width / 2 - this.padding / 2) :
                this.split.width - 2 * this.padding;
            sp.definitionHeight = ishorizontal ?
                this.split.height - this.iconSize - 2 * this.padding :
                Math.round(this.split.height / 2 - 2 * this.padding - this.iconSize - this.iconSize / 2);
            sp.definitionTop = this.iconSize + 2 * this.padding;
            sp.definition = this.createDOMElement('div', {
                parent: sp.parent,
                classnames: 'mmogame-quiz-aduelsplit-definition',
                styles: {
                    position: 'absolute',
                    left: `${this.padding}px`,
                    top: `${sp.definitionTop}px`,
                    width: `${sp.definitionWidth}px`,
                    height: `${sp.definitionHeight}px`,
                    overflow: 'hidden',
                    background: this.colorDefinition,
                    color:  this.getContrastingColor(this.colorDefinition),
                    padding: `${this.padding}px`,
                }
            });
            sp.definition.innerHTML = this.formatText(sp.attempts[0].definition, sp.attempts[0].numattempt + ". ");

            sp.definition.addEventListener('click', () => {
                this.continueAnswer(split);
            });

            const disabled = false;

            sp.answersLeft = ishorizontal ? sp.definitionWidth + 2 * this.padding : this.padding;
            sp.answersWidth = this.split.width - sp.answersLeft;
            sp.answersTop = ishorizontal ?
                this.iconSize + 2 * this.padding :
                sp.definitionTop + sp.definitionHeight + 2 * this.padding;
            sp.answersHeight = this.split.height - sp.answersTop - 2 * this.padding;

            this.createAnswerMultichoice(
                sp.parent,
                split,
                sp,
                disabled);

            this.computeFontSize(split, sp);

            this.endofgame = false;
        }

        setColors(colors) {
            super.setColors(colors);

            this.colorDefinition = this.getColorHex(colors[1]);
            this.colorTip = this.getColorHex(colors[2]);
            this.colorScore = colors[3];
        }

        /**
         * Creates multiple-choice answer options.
         *
         * @param {object} parent
         * @param {number} split
         * @param {object} sp
         * @returns {number} The total height used by the answer options.
         */
        createAnswerMultichoice(parent, split, sp) {
            const attempt = sp.attempts[0];
            const n = attempt.answers.length;
            sp.aItemRadio = Array(n);
            sp.aItemLabel = Array(n);

            let aRandom = this.computeRandom(n);

            // Iterate over each answer
            for (let i = 0; i < n; i++) {
                this.createAnswerMultichoiceItem(split, sp, i, aRandom, attempt);
            }

            sp.selectedAnswer = -1;
            sp.aRandom = aRandom;
            sp.timestart = Math.round(Math.floor(Date.now() / 1000));

            return top;
        }

        createAnswerMultichoiceItem(split, sp, i, aRandom, attempt) {
            const ans = aRandom[i];
            const label = this.createDOMElement('label', {
                parent: sp.parent,
                classnames: 'mmogame-quiz-aduelsplit-label' + i,
                styles: {
                    position: 'absolute',
                    top: `${top}px`,
                    color: this.getContrastingColor(this.colorBackground),
                    align: "left",
                    marginTop: 0,
                    marginBottom: 0,
                    overflow: 'visible',
                    lineHeight: 'normal',
                }
            });
            label.innerHTML = this.formatText(attempt.answers[ans], (i + 1) + ". ");
            label.htmlFor = "mmogame_quiz_aduelsplit-input" + i;

            // Create the checkbox
            const checked = false;
            const item = this.createRadiobox(sp.parent, 0, this.colorBackground, this.colorScore, checked);
            item.style.position = "absolute";
            item.style.left = sp.answersLeft + "px";
            item.id = "mmogame_quiz_input" + i;

            // Event listeners for interactions
            item.addEventListener('click', () => {
                if (!item.classList.contains("disabled")) {
                    this.onClickRadio(split, i, this.colorBackground2, this.colorScore);
                    sp.selectedAnswer = i;
                    this.selectAnswer(split);
                    this.updateRanks();
                }
            });

            label.addEventListener('click', () => {
                if (this.splits[split].stateShowCorrect !== undefined) {
                    return; // Disabled.
                }
                this.onClickRadio(split, i, this.colorBackground2, this.colorScore);
                sp.selectedAnswer = i;
                this.selectAnswer(split);
                this.updateRanks();
            });

            sp.aItemRadio[i] = item;
            sp.aItemLabel[i] = label;
        }

        computeRandom(n) {
            // Create an array with numbers from 0 to n
            let aRandom = Array.from({length: n}, (_, i) => i);
            // Shuffle the array using Fisher-Yates algorithm
            for (let i = n - 1; i > 0; i--) {
                let j = Math.floor(Math.random() * (i + 1)); // Generate a random index
                [aRandom[i], aRandom[j]] = [aRandom[j], aRandom[i]]; // Swap elements
            }
            return aRandom;
        }

        computeFontSize(split, sp) {
            const ishorizontal = split.width >= split.height;
            let minSize = 10;
            const bodyFontSize = parseFloat(getComputedStyle(document.documentElement).fontSize);
            let maxSize = Math.min(2 * bodyFontSize, this.iconSize / 2, sp.answersWidth);
            const precision = 0.1;

            const n = sp.aItemLabel.length;
            const div = sp.definition;
            const style = div.style;

            const maxHeight = ishorizontal
                ? sp.answersHeight - this.padding - this.iconSize / 2
                : sp.answersHeight - this.iconSize - this.padding;

            style.width = sp.definitionWidth + "px";
            style.height = sp.definitionHeight + "px";

            let bestFit = minSize;

            style.overflow = 'hidden';
            // Binary search for the largest font size that still fits
            while ((maxSize - minSize) > precision) {
                const fontSize = (minSize + maxSize) / 2;
                style.fontSize = fontSize + "px";

                // Check whether the definition overflows
                let isbig = (
                    div.scrollWidth > div.clientWidth ||
                    div.scrollHeight > div.clientHeight
                );

                if (!isbig) {
                    let sum = 0;
                    const maxWidth = Math.round(sp.answersWidth - fontSize - 3 * this.padding);

                    for (let i = 0; i < n; i++) {
                        const elem = sp.aItemLabel[i];
                        elem.style.fontSize = `${fontSize}px`;
                        elem.style.width = (maxWidth - 1) + "px";
                        elem.style.height = 'auto';

                        if (elem.scrollWidth > elem.clientWidth) {
                            isbig = true;
                            break;
                        }

                        sum += elem.scrollHeight + this.padding;
                    }

                    if (sum > maxHeight) {
                        isbig = true;
                    }
                }

                if (isbig) {
                    maxSize = fontSize;
                } else {
                    bestFit = fontSize;
                    minSize = fontSize;
                }
            }

            const fontSize = Math.floor(bestFit * 10) / 10;
            sp.fontSize = fontSize;

            // Apply final font size
            sp.definition.style.fontSize = fontSize + "px";

            let top = sp.answersTop;
            const leftLabel = sp.answersLeft + Math.round(fontSize) + this.padding;
            sp.aItemLabelTop = Array(n);

            for (let i = 0; i < n; i++) {
                const elem = sp.aItemLabel[i];
                elem.style.fontSize = `${fontSize}px`;
                elem.style.top = `${top}px`;
                elem.style.left = `${leftLabel}px`;
                elem.style.width = `${Math.round(sp.answersWidth - fontSize - 2 * this.padding)}px`;
                elem.style.height = 'auto';

                const radio = sp.aItemRadio[i];
                radio.style.width = `${Math.round(fontSize)}px`;
                radio.style.height = `${elem.scrollHeight}px`;
                radio.style.top = `${top}px`;
                this.drawRadio(radio, 0xFFFFFF, 0x808080);

                sp.aItemLabelTop[i] = top;
                top += elem.scrollHeight + this.padding;
            }

            const space = sp.fontSize;
            if (top + this.iconSize + this.padding + n * space < split.height) {
                for (let i = 1; i < n; i++) {
                    const elem = sp.aItemLabel[i];
                    const newTop = parseInt(elem.style.top) + i * sp.fontSize;
                    elem.style.top = `${newTop}px`;
                    sp.aItemRadio[i].style.top = `${newTop}px`;
                }
                top += (n - 1) * space;
            }

            if (ishorizontal) {
                // If there is enough vertical space, expand the definition block height
                if (sp.definition.scrollHeight < top - sp.answersTop) {
                    sp.definition.style.height = `${top - sp.answersTop}px`;
                }
            } else {
                style.height = `${sp.definitionHeight}px`;
            }

            return fontSize;
        }

        copySplitData(splitInfo, split) {
            const sp = this.splits[split];
            const info = this.info;
            // Copy attempt ids.
            let queryids = info.attemptqueryids[splitInfo].split(",");

            const aduelcorrects = info.aduelcorrects[splitInfo].split(',');
            const numattempts = info.numattempts[splitInfo].split(",");
            sp.auserid = info.auserids[splitInfo];
            let attempts = info.attempts[splitInfo].split(",");
            sp.attempts = [];
            sp.score = parseInt(info.grades[splitInfo]);
            sp.rank = parseInt(info.ranks[splitInfo]);
            sp.countcorrect = info.countcorrect[splitInfo];
            for (let i = 0; i < attempts.length; i++) {
                let item = {attemptid: attempts[i]};
                item.queryid = queryids[i];
                item.islastcorrect = info.islastcorrect[item.queryid];
                item.definition = info.querydefinitions[item.queryid];
                item.querytip = info.querytips[item.queryid];
                item.answerids = info.queryanswerids[item.queryid].split(",");
                item.answerids0 = info.queryanswerids0[item.queryid].split(",");
                item.answers = [];
                item.numattempt = numattempts[i];
                item.aduelcorrect = (parseInt(aduelcorrects[i]) !== 0);
                item.queryrank = info.queryranks[item.queryid];
                for (let j = 0; j < item.answerids.length; j++) {
                    item.answers.push(info.answertexts[item.answerids[j]]);
                }

                sp.attempts.push(item);
            }
            sp.aduel = this.info.aduels[splitInfo];
            sp.aduelavatar = this.info.aduelavatars[splitInfo];
        }

        moveX(timestamp, split, num, direction, steps) {
            if (this.screen === 0) {
                super.moveX(timestamp, split, num, direction, steps);
            }
        }

        moveY(timestamp, split, num, direction, steps) {
            if (this.screen === 0) {
                super.moveY(timestamp, split, num, direction, steps);
                return;
            }

            if (this.splits[split].isWaitingContinue) {
                return;
            }

            if (direction < 0) {
                this.moveAnswer(timestamp, split, -1);
            } else {
                this.moveAnswer(timestamp, split, 1);
            }
        }

        moveAnswer(timestamp, split, move) {
            let sp = this.splits[split];

            if (timestamp - sp.lastUpdateTime < this.updateDelay) {
                return;
            }
            if (sp.aItemRadio === undefined) {
                return;
            }
            sp.lastUpdateTime = timestamp;

            sp.selectedAnswer = sp.selectedAnswer === -1 ? 0 :
                (sp.aItemRadio.length + sp.selectedAnswer + move) % sp.aItemRadio.length;

            this.onClickRadio(split, sp.selectedAnswer, this.colorBackground2, this.colorScore);
        }

        /**
         * Handles radio button click events for answers.
         *
         * @param {number} split
         * @param {number} index - The index of the clicked radio button.
         * @param {string} colorBack - The background color for the radio button.
         * @param {string} color - The color for the radio button when selected.
         */
        onClickRadio(split, index, colorBack, color) {
            let sp = this.splits[split];
            if (sp.aItemRadio[index].classList.contains("disabled")) {
                return;
            }

            // Update the selected radio button and deselect others
            sp.aItemRadio.forEach((item, i) => {
                const isDisabled = item.classList.contains("disabled");
                if (i === index) {
                    item.classList.add("checked");
                    sp.answerid = sp.answerids[i];
                    sp.aItemLabel[i].style.textDecoration = "underline";
                } else {
                    item.classList.remove("checked");
                    sp.aItemLabel[i].style.textDecoration = "none";
                }

                this.drawRadio(item, isDisabled ? colorBack : 0xFFFFFF, color);
            });
        }

        // Butttons: 8 = Select, 10 = Joystick pressed.
        updateGamepad(timestamp, split, gamepad) {
            super.updateGamepad(timestamp, split, gamepad);

            let change = false;
            if (gamepad.buttons[4].value || gamepad.buttons[5].value || gamepad.buttons[6].value ||
                gamepad.buttons[7].value || gamepad.buttons[8].value) {
                // Button select or fire is pressed (means continue).
                let sp = this.splits[split];

                if (sp.isWaitingContinue && (sp.buttonNext !== undefined)) {
                    this.continueAnswer(split);
                }
            } else if (gamepad.buttons[10].value) { // Button joysticpress is pressed.
                if (this.selectAnswer(split, this.splits[split].selectedAnswer)) {
                    change = true;
                }
            }
            if (change) {
                this.updateRanks();
            }

            if (this.splits !== undefined && split < this.splits.length) {
                const sp = this.splits[split];
                if (sp.aItemLabel !== undefined && !sp.isWaitingContinue) {
                    const n = Math.min(sp.aItemLabel.length, 4);
                    // Check if buttons 1..4 is pressed.
                    for (let i = 0; i < n; i++) {
                        if (gamepad.buttons[i].pressed) {
                            this.onClickRadio(split, i, this.colorBackground2, this.colorScore);
                            sp.selectedAnswer = i;
                            this.selectAnswer(split);
                            this.updateRanks();
                            sp.isWaitingContinue = true;
                        }
                    }
                }
            }
        }

        selectAnswer(split) {
            // Let change = false;
            let sp = this.splits[split];

            if (sp.attempts === undefined || sp.attempts.length === 0) {
                return false; // Wait answer from server, so no attempts available.
            }

          if (sp.selectedAnswer >= 0 && !sp.isWaitingContinue) {
                sp.isWaitingContinue = true;
                const pos = sp.aRandom[sp.selectedAnswer];
                const isWizard = sp.questionTip !== undefined;
                if (pos === 0) {
                    // Correct answer.
                    const addscore = isWizard ? 1 : sp.aItemRadio.length - 1;
                    sp.score += addscore;
                    if (sp.aduel >= 0) {
                        if (!sp.attempts[0].aduelcorrect) {
                            sp.score += sp.aItemRadio.length - 1;
                        }
                    }
                    if (sp.attempts[0].islastcorrect === 0) {
                        sp.countcorrect++;
                        this.updatePercent(sp);
                    }
                    this.updateScore(sp);
                    this.showCorrect(sp, true, addscore, 0);
                    this.sendAnswer(split, true, isWizard, false);
                } else {
                    // Wrong answer.
                    let addscore = -Math.min(sp.score, 1);
                    sp.score += addscore;
                    this.updateScore(sp);

                    let addscoreaduel = 0;
                    if (sp.aduel >= 0) {
                        if (sp.attempts[0].aduelcorrect) {
                            addscoreaduel = sp.aItemRadio.length - 1;
                            this.updateScoreOpponent(sp, addscoreaduel);
                        }
                    }
                    this.showCorrect(sp, false, addscore, addscoreaduel);
                    this.sendAnswer(split, false, isWizard);
                }

                return true;
            }
            return false;
        }

        continueAnswer(split) {
            const sp = this.splits[split];
            if (!sp.isWaitingContinue) {
                return;
            }
            sp.isWaitingContinue = false;
            // Remove the question that is used.
            sp.attempts.shift();
            this.showNextQuestion(split);
        }

        updateRanks() {
            if (this.splits.length === 1) {
                return;
            }
            for (let i = 0; i < this.splits.length; i++) {
                this.splits[i].rank = 0;
            }
            let rank = 1;
            for (;;) {
                let found = false;
                let max = -1;
                for (let i = 0; i < this.splits.length; i++) {
                    const sp = this.splits[i];
                    if (sp.rank === 0) {
                        found = true;
                        if (sp.score > max) {
                            max = sp.score;
                        }
                    }
                }
                if (!found) {
                    break; // Finished.
                }
                let count = 0;
                for (let i = 0; i < this.splits.length; i++) {
                    const sp = this.splits[i];
                    if (sp.score === max) {
                        sp.rank = rank;
                        this.updateRank(sp);
                        count++;
                    }
                }
                rank += count;
            }
        }

        updateRank(sp) {
            if (this.splits.length === 1 || sp.player === undefined || sp.player.lblRank === undefined) {
                return;
            }
            if (sp.rank === sp.rankcache) {
                return;
            }
            sp.player.lblRank.innerHTML = '#' + sp.rank;
            sp.rankcache = sp.rank;
        }

        updateScore(sp) {
            sp.player.lblScore.innerHTML = sp.score;
            sp.player.lblRank.innerHTML = sp.rank !== '' ? '#' + sp.rank : '';
        }

        updatePercent(sp) {
            sp.percent.lblScore.innerHTML = this.info.countquestion > 0 ? sp.countcorrect + " / " + this.info.countquestion : '';
            this.autoResizeText(sp.percent.lblScore, 0.8 * this.iconSize, sp.percent.cellSize,
                false, 0, 0);
        }

        sendAnswer(split, iscorrect, isWizard, isSkip, isIdea) {
            const sp = this.splits[split];

            if (sp.attempts.length === 0) {
                return; // No attempts.
            }

            const attempt = sp.attempts[0];

            const tools = (isWizard !== undefined && isWizard ? 1 : 0) +
                (isSkip !== undefined && isSkip ? 2 : 0) +
                (isIdea !== undefined && isIdea ? 4 : 0);

            const pos = sp.aRandom[sp.selectedAnswer];
            const info = {
                split: split,
                attemptid: attempt.attemptid,
                iscorrect: iscorrect,
                answer: attempt.answerids0[pos],
                tools: tools,
                timestart: sp.timestart,
                timeanswer: Math.round(Math.floor(Date.now() / 1000)),
            };
            sp.server.push(info);
        }

        showCorrect(sp, iscorrect, addscore, addscoreaduel) {
            const correctPos = sp.aRandom.indexOf(0);
            const topCorrect = sp.aItemLabelTop[0];
            const topIcon = topCorrect + this.padding + sp.aItemLabel[correctPos].offsetHeight;
            const topIncorrect = topIcon + this.iconSize + this.padding;
            const leftLabel = parseFloat(sp.aItemRadio[0].style.left);

            sp.stateShowCorrect = true;
            // Move correct answer to position 0.
            for (let i = 0; i < sp.aItemRadio.length; i++) {
                this.fadeOut(sp, sp.aItemRadio[i], this.durationAnination);
                if (i !== correctPos && i !== sp.selectedAnswer) {
                    this.fadeOut(sp, sp.aItemLabel[i], this.durationAnination);
                } else {
                    sp.aItemRadio[i].classList.add("disabled");
                    sp.aItemLabel[i].classList.add("disabled");
                }
            }
            if (correctPos !== 0) {
                this.moveElementSmoothly(sp, sp.aItemLabel[correctPos], topCorrect, this.durationAnination);
            }

            const sizeRadio = Math.round(sp.fontSize);
            sp.correctSmall = this.createCorrectIcon(
                sp,
                sp.parent,
                leftLabel,
                sp.aItemLabelTop[sp.selectedAnswer],
                topCorrect,
                sizeRadio,
                true
            );

            if (iscorrect) {
                sp.incorrectSmall = undefined;
            } else {
                const label = sp.aItemLabel[sp.selectedAnswer];

                label.style.textDecoration = "none";

                this.moveElementSmoothly(
                    sp,
                    label,
                    topIncorrect,
                    this.durationAnination);
                sp.incorrectSmall = this.createCorrectIcon(
                    sp,
                    sp.parent,
                    leftLabel,
                    sp.aItemLabelTop[sp.selectedAnswer],
                    topIncorrect,
                    sizeRadio,
                    false
                );
            }

            sp.timeoutStrip = setTimeout(() => {
                sp.timeoutStrip = undefined;

                if (sp.stateShowCorrect === undefined) {
                    return; // We are in next question.
                }

                this.createQuestionTip(sp);
                this.showCorrectStrip(sp, topIcon, iscorrect, addscore, addscoreaduel);
                this.createNextButton(sp, correctPos, topIncorrect);

                sp.aItemLabel[correctPos].style.opacity = "1";
                sp.aItemLabel[correctPos].style.display = "block";
                sp.aItemLabel[sp.selectedAnswer].style.opacity = "1";
                sp.aItemLabel[sp.selectedAnswer].style.display = "block";
            }, this.durationAnination);
        }

        createCorrectIcon(sp, parent, left, topSource, topDestination, sizeRadio, iscorrect) {
            const div = this.createDiv(
                parent,
                'mmogame-quiz-aduelsplit-iscorrect',
                left,
                topSource,
                sizeRadio,
                sizeRadio);
            div.innerHTML = this.getSVGcorrect(sizeRadio, iscorrect, this.colorScore, this.colorScore);
            div.style.zIndex = '1';
            if (topSource !== topDestination) {
                this.moveElementSmoothly(sp, div, topDestination, this.durationAnination);
            }

            return div;
        }

        fadeOut(sp, element, duration) {
            let opacity = 1;

            let interval = setInterval(function() {
                if (sp.stateShowCorrect === undefined) {
                    element.style.display = 'block';
                    element.style.opacity = 1;
                    return;
                }
                if (opacity <= 0) {
                    clearInterval(interval);
                    element.style.display = 'none'; // Hide it.
                } else {
                    opacity -= 0.05; // Μειώνουμε την αδιαφάνεια
                    element.style.opacity = opacity; // Εφαρμόζουμε την αδιαφάνεια στο στοιχείο
                }
            }, duration / 20); // Ορίζουμε πόσο γρήγορα θα αλλάξει η αδιαφάνεια
        }

        moveElementSmoothly(sp, element, finalY, duration) {
            const startY = element.offsetTop; // Αρχική θέση Y
            const deltaY = finalY - startY; // Διαφορά για Y
            const startTime = performance.now(); // Ώρα εκκίνησης για υπολογισμό του χρόνου

            const animate = () => {
                if (sp.stateShowCorrect === undefined) {
                    return;
                }
                const currentTime = performance.now();
                const elapsedTime = currentTime - startTime;

                // Υπολογισμός της αναλογίας του χρόνου (0 - 1) για τη μετάβαση
                const progress = Math.min(elapsedTime / duration, 1);

                // Υπολογισμός της νέας θέσης
                const currentY = startY + deltaY * progress;
                // Εφαρμογή της νέας θέσης
                element.style.top = `${currentY}px`;

                // Συνεχίζουμε το animation αν δεν έχουμε φτάσει στη νέα θέση
                if (progress < 1) {
                    requestAnimationFrame(animate);
                } else {
                    setTimeout(function() {
                        element.style.display = "block";
                    }, 500);
                }
            };

            // Ξεκινάμε την κίνηση
            animate();
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

        hideQuestion(sp) {
            this.removeCorrectIcons(sp);

            for (let i = 0; i < sp.aItemLabel.length; i++) {
                const label = sp.aItemLabel[i];
                label.style.display = "none";

                const radio = sp.aItemRadio[i];
                radio.style.display = "none";
            }
        }

        showNextQuestion(split) {
            const sp = this.splits[split];

            if (sp.questionTip !== undefined) {
                sp.parent.removeChild(sp.questionTip);
                sp.questionTip = undefined;
                sp.definition.style.height = sp.definitionHeightOld + "px";
            }

            sp.isWaitingContinue = false;
            sp.timestart = Math.round(Math.floor(Date.now() / 1000));
            sp.stateShowCorrect = undefined;

            this.removeCorrectIcons(sp);
            const n = sp.attempts.length > 0 ? sp.attempts[0].answers.length : 0;

            sp.aRandom = this.computeRandom(n);
            for (let i = 0; i < sp.aItemLabel.length; i++) {
                const label = sp.aItemLabel[i];
                label.style.top = sp.aItemLabelTop[i];
                label.style.display = "block";
                label.style.opacity = 1;
                label.style.textDecoration = "none";
                label.classList.remove("disabled");

                const radio = sp.aItemRadio[i];
                radio.style.display = "block";
                radio.style.opacity = 1;
                radio.classList.remove("checked");
                radio.classList.remove("disabled");
                this.drawRadio(radio, 0xFFFFFF, this.colorScore);
                sp.selectedAnswer = -1;
            }

            if (sp.attempts.length === 0) {
                sp.definition.innerHTML = '';
                for (let i = 0; i < sp.aItemLabel.length; i++) {
                    sp.aItemLabel[i].innerHTML = '';
                }
                this.hideQuestion(sp);

                this.askNextQuestions(sp);
                return;
            }

            // Shows the next question.
            // sp.definition.innerHTML = sp.attempts[0].numattempt + ". " + sp.attempts[0].definition;
            sp.definition.innerHTML = this.formatText(sp.attempts[0].definition, sp.attempts[0].numattempt + ". ");
            this.autoResizeText(sp.definition, sp.definitionWidth, sp.definitionHeight, true, 0, 0);
            const attempt = sp.attempts[0];
            this.makeItemLength(split, sp, attempt.answers.length);
            for (let i = 0; i < sp.aItemLabel.length; i++) {
                const ans = sp.aRandom[i];
                sp.aItemLabel[i].innerHTML = this.formatText(attempt.answers[ans], (i + 1) + ": ");
            }

            this.computeFontSize(split, sp);

            if (this.info.countquestion > 0) {
                let percent = sp.attempts[0].queryrank / this.info.countquestion;
                this.updateDifficultyRing(sp.ring, percent);
            }
        }

        makeItemLength(split, sp, n) {
            while (n < sp.aItemLabel.length) {
                sp.parent.removeChild(sp.aItemLabel[n]);
                sp.parent.removeChild(sp.aItemRadio[n]);
                sp.aItemLabel.splice(n, 1);
                sp.aItemRadio.splice(n, 1);
            }

            while (sp.aItemLabel.length < n) {
                this.createAnswerMultichoiceItem(split, sp, sp.aItemLabel.length, sp.aRandom, sp.attempts[0]);
            }
        }

        askNextQuestions(sp) {
            this.showCursor(sp, true);

            if (sp !== undefined && sp.aduelavatarelement !== undefined) {
                sp.aduelavatarelement.remove();
                sp.aduelavatarelement = undefined;
            }
            let splits = [];
            let attempts = [];
            let iscorrects = [];
            let answers = [];
            let timestarts = [];
            let timeanswers = [];
            let returnsplits = [];
            let tools = [];

            let spidea = -1;
            for (let i = 0; i < this.splits.length; i++) {
                const sp2 = this.splits[i];
                if (sp2.attempts.length === 0) {
                    returnsplits.push(i);
                }
                for (let j = 0; j < sp2.server.length; j++) {
                    const temp = sp2.server[j];
                    splits.push(temp.split);
                    attempts.push(temp.attemptid);
                    iscorrects.push(temp.iscorrect ? 1 : 0);
                    answers.push(temp.answer);
                    timestarts.push(temp.timestart);
                    timeanswers.push(temp.timeanswer);
                    tools.push(temp.tools);
                    if (temp.tools === 4) {
                        spidea = i;
                    }
                }
            }
            if (splits.length === 0) {
                // Autorepair.
                for (let i = 0; i < this.splits.length; i++) {
                    if (this.splits[i].attempts.length === 0) {
                        splits.push(i);
                    }
                }
                if (splits.length === 0) {
                    splits.push(0);
                }
            }
            require(['core/ajax'], (Ajax) => {
                // Defining the parameters to be passed to the service
                let params = {
                    mmogameid: this.mmogameid,
                    kinduser: this.kinduser,
                    user: this.user,
                    splits: splits.join(','),
                    attempts: attempts.join(','),
                    iscorrects: iscorrects.join(','),
                    answers: answers.join(','),
                    timestarts: timestarts.join(','),
                    timeanswers: timeanswers.join(','),
                    returnsplits: returnsplits.join(','),
                    tools: tools.join(','),
                };
                // Calling the service through the Moodle AJAX API
                let sendAnswers = Ajax.call([{
                    methodname: 'mmogametype_quiz_send_answers_split',
                    args: params
                }]);
                // Handling the response
                sendAnswers[0].done(({avatars, attempts, attemptqueryids, querydefinitions, querytips,
                                         queryanswerids, numattempts, answertexts, aduels,
                                         aduelavatars, aduelcorrects, auserids, queryanswerids0,
                                         grades, savedattempts, countquestion, countcorrect, islastcorrect, ranks, queryranks,
                                         state, statetime}) => {

                    if (spidea >= 0) {
                        this.showIdea(spidea, querydefinitions, queryanswerids0, querytips, answertexts);
                        return;
                    }

                    this.info = {
                        avatars: avatars,
                        attempts: attempts,
                        paletteid: this.paletteid,
                        attemptqueryids: attemptqueryids,
                        numattempts: numattempts,
                        querydefinitions: querydefinitions,
                        querytips: querytips,
                        queryanswerids: queryanswerids,
                        answertexts: answertexts,
                        aduels: aduels,
                        aduelavatars: aduelavatars,
                        aduelcorrects: aduelcorrects,
                        auserids: auserids,
                        queryanswerids0: queryanswerids0,
                        grades: grades,
                        countquestion: countquestion,
                        countcorrect: countcorrect,
                        islastcorrect: islastcorrect,
                        ranks: ranks,
                        queryranks: queryranks,
                        state: state,
                        statetime: statetime,
                    };
                    this.removeFromServer(savedattempts);
                    if (this.info.state === 0) {
                        this.createDivMessageStart(this.getStringM('js_wait_to_start'));
                        setTimeout(() => {
                            this.callGetAttemptsSplit();
                        }, 15000);
                        return;
                    }
                    for (let i = 0; i < returnsplits.length; i++) {
                        const split = returnsplits[i];
                        this.copySplitData(i, split);
                        this.checkShowAduel(this.splits[split]);
                        this.showNextQuestion(split);
                        this.updatePercent(this.splits[split]);
                    }
                    this.showCursor(sp, false);
                }).fail((error) => {
                    this.showCursor(sp, false);
                    return error;
                });
            });
        }

        computeFontScorePercent(sp) {
            if (sp.position === 0) {
                const player = sp.player;
                player.lblScore.innerHTML = "99999";
                this.autoResizeText(player.lblScore, this.iconSize, player.cellSize, false, 0, 0);
                const fontSize = player.lblScore.style.fontSize;
                const lineHeight = Math.round(parseFloat(fontSize));
                if (player.lblRank !== undefined) {
                    player.lblRank.style.fontSize = fontSize;
                    player.lblRank.lineHeight = lineHeight;
                }
                player.lblScore.lineHeight = lineHeight;
                player.lblScore.innerHTML = "";
            } else {
                const player = sp.player;
                let fontSize = this.splits[0].player.lblRank.style.fontSize;
                const lineHeight = Math.round(parseFloat(fontSize));
                if (player.lblRank !== undefined) {
                    player.lblRank.style.fontSize = fontSize;
                    player.lblRank.lineHeight = lineHeight;
                }
                player.lblScore.style.fontSize = fontSize;
                player.lblScore.lineHeight = lineHeight;
            }
        }

        removeCorrectIcons(sp) {
            if (sp.timeoutStrip !== undefined) {
                clearTimeout(sp.timeoutStrip);
            }

            if (sp.stripCorrect !== undefined) {
                sp.stripCorrect.remove();
                sp.stripCorrect = undefined;
            }

            if (sp.correctSmall !== undefined) {
                sp.correctSmall.remove();
                sp.correctSmall = undefined;
            }

            if (sp.incorrectSmall !== undefined) {
                sp.incorrectSmall.remove();
                sp.incorrectSmall = undefined;
            }

            if (sp.buttonNext !== undefined) {
                sp.buttonNext.remove();
                sp.buttonNext = undefined;
            }
        }

        updateScoreOpponent(sp, addscore) {

            const avatar = sp.aduelavatar;
            for (let i = 0; i < this.splits.length; i++) {
                if (this.splits[i].avatarfile === avatar) {
                    const newsplit = this.splits[i];
                    newsplit.score += addscore;
                    this.updateScore(newsplit);
                    break;
                }
            }
        }

        checkShowAduel(sp) {
            if (sp.aduelavatar === '' || sp.aduelavatar === undefined) {
                if (sp.aduelavatarelement !== undefined) {
                    sp.aduelavatarelement.remove();
                    sp.aduelavatarelement = undefined;
                }
                return; // Plays alone
            }
            let leftAvatar = Math.round(this.padding + this.iconSize / 2);
            sp.aduelavatarelement = this.createDOMElement('img', {
                classname: `mmogame-quiz-avatar`,
                parent: sp.parent,
                styles: {
                    position: 'absolute',
                    left: `${leftAvatar + 2 * this.iconSize + 2 * this.padding}px`,
                    top: `${this.padding}px`,
                    height: `${this.iconSize}px`,
                    maxWidth: `${this.iconSize}px`,
                    transform: 'translateX(-50%)',
                },
                attributes: {
                    src: 'assets/avatars/' + sp.aduelavatar,
                    alt: this.getStringM('js_help'),
                    role: 'button',
                },
            });
        }

        createAddScore(sp, left, iconSize, addscore) {
            if (addscore === 0) {
                return false;
            }

            if (addscore > 0) {
                addscore = "+" + addscore;
            }

            const label = this.createDOMElement('div', {
                parent: sp.stripCorrect,
                classnames: `mmogame-quiz-addscore`,
                styles: {
                    position: 'absolute',
                    left: `${left}px`,
                    width: `${iconSize}px`,
                    top: `0`,
                    height: `${iconSize}px`,
                    lineHeight: `${iconSize}px`,
                    textAlign: 'center',
                    color: this.getContrastingColor(this.colorBackground),
                    border: "0px solid " + this.getColorHex(this.colorBackground),
                    boxShadow: "inset 0 0 0.125em rgba(255, 255, 255, 0.75)",
                    background: this.getColorHex(this.colorBackground),
                    borderRadius: `${iconSize}px`,
                    fontSize: sp.aItemLabel[0].style.fontSize,
                },
                attributes: {
                    title: this.getStringM('js_addscore'),
                },
            });

            label.innerHTML = addscore;

            return label;
        }

        showCorrectStrip(sp, top, iscorrect, addscore, addscoreaduel) {
            const iconSize = Math.max(1, Math.min(this.iconSize, Math.ceil(sp.answersWidth / 4 - this.padding - this.padding / 4)));
            sp.stripCorrect = this.createDOMElement('div', {
                parent: sp.parent,
                classnames: 'mmogame-quiz-split',
                styles: {
                    position: 'absolute',
                    left: `${parseInt(sp.aItemRadio[0].style.left)}px`,
                    top: `${top}px`,
                    width: `${sp.answersWidth}px`,
                    height: `${iconSize}px`,
                    overflow: 'hidden',
                }
            });

            this.createAddScore(sp, 0, iconSize, addscore);

            this.createDOMElement('img', {
                classname: `mmogame-quiz-avatar`,
                parent: sp.stripCorrect,
                styles: {
                    position: 'absolute',
                    left: `${iconSize + this.padding}px`,
                    top: `0`,
                    height: `${iconSize}px`,
                    maxWidth: `${iconSize}px`,
                },
                attributes: {
                    src: 'assets/avatars/' + sp.avatarfile,
                    alt: this.getStringM('js_help'),
                    role: 'button',
                },
            });
            this.createCorrectIcon(sp, sp.stripCorrect, iconSize + this.padding, 0, 0, this.iconSize, iscorrect);

            if (sp.aduelavatar !== '') {
                let leftAduel = 2 * (iconSize + this.padding);
                this.createDOMElement('img', {
                    classname: `mmogame-quiz-avatar`,
                    parent: sp.stripCorrect,
                    styles: {
                        position: 'absolute',
                        left: `${leftAduel}px`,
                        top: `0`,
                        height: `${iconSize}px`,
                        maxWidth: `${iconSize}px`,
                    },
                    attributes: {
                        src: 'assets/avatars/' + sp.aduelavatar,
                        alt: this.getStringM('js_help'),
                        role: 'button',
                    },
                });
                const correctAduel = sp.attempts[0].aduelcorrect;
                this.createCorrectIcon(sp, sp.stripCorrect, leftAduel, 0, 0, iconSize, correctAduel);
                this.createAddScore(sp, leftAduel + iconSize + this.padding, iconSize, addscoreaduel);
            }
        }

        async createNextButton(sp, correctPos, topIncorrect) {
            const time = correctPos === sp.selectedAnswer ?
                this.timeBeforeShowNextButtonCorrect : this.timeBeforeShowNextButtonError;
            sp.aItemLabel[sp.selectedAnswer].style.display = 'block';
            setTimeout(async() => {
                if (sp.selectedAnswer < 0 || sp.selectedAnswer >= sp.aItemLabel.length) {
                    return;
                }
                sp.aItemLabel[sp.selectedAnswer].style.opacity = 1;
                sp.aItemLabel[correctPos].style.opacity = 1;
                sp.aItemLabel[correctPos].style.display = 'block';

                const h = await this.waitForHeight(sp.aItemLabel[sp.selectedAnswer], 10, 30, this.iconSize);
                const left = sp.answersLeft + Math.round(sp.answersWidth / 2 - this.iconSize);
                let top;

                // Computes the height of selected answer.
                if (correctPos === sp.selectedAnswer) {
                    top = sp.answersTop + h + 2 * this.padding + this.iconSize;
                } else {
                    top = topIncorrect + h + this.padding;
                }
                let iconSize = this.iconSize;
                if (top + this.iconSize > this.split.height) {
                    // Check if nextButton is out of screen.
                    iconSize = Math.round(iconSize / 2);
                    if (top + iconSize > this.split.height) {
                        top = this.split.height - iconSize;
                    }
                }
                if (sp.buttonNext !== undefined) {
                    return;
                }
                sp.buttonNext = super.createImageButton(sp.parent, 'mmogame-quiz-next',
                    left, top, 0, iconSize, 'assets/next.svg');
                sp.buttonNext.title = this.getStringM('js_next_question');
                sp.buttonNext.addEventListener("click", () => {
                    this.continueAnswer(sp.position);
                });
            }, time);
        }

        waitForHeight(el, maxRetries = 10, delay = 50, fallbackHeight = 24) {
            return new Promise((resolve) => {
                let tries = 0;
                /**
                 * Computes getBoundingClientRect.
                 */
                function check() {
                    const h = el.getBoundingClientRect().height;
                    if (h > 0) {
                        resolve(h);
                    } else {
                        tries++;
                        if (tries >= maxRetries) {
                            resolve(fallbackHeight);
                        } else {
                            setTimeout(check, delay);
                        }
                    }
                }
                check();
            });
        }

        removeFromServer(savedattempts) {
            for (let i = 0; i < this.splits.length; i++) {
                const sp = this.splits[i];
                let j = 0;
                while (j < sp.server.length) {
                    const server = sp.server[j];
                    const index = savedattempts.indexOf(parseInt(server.attemptid));
                    if (index !== -1) {
                        sp.server.splice(j, 1);
                        savedattempts.splice(index, 1);
                    } else {
                        j++;
                    }
                }
            }
        }

        createScreenKeyboard() {
            let instance = this;
            document.addEventListener('keydown', function(event) {
                const validKeys = ['1', '2', '3', '4', '5', '6', '7', '8', '9'];
                if (validKeys.includes(event.key)) {
                    let split = -1;
                    switch (event.location) {
                        case KeyboardEvent.DOM_KEY_LOCATION_NUMPAD:
                            split = 1;
                            break;
                        case KeyboardEvent.DOM_KEY_LOCATION_STANDARD:
                            split = 0;
                            break;
                    }
                    if (split >= 0 && split <= instance.splits.length) {
                        const sp = instance.splits[split];
                        if (!sp.isWaitingContinue) {
                            const i = parseInt(event.key) - 1;
                            if (i < sp.aItemLabel.length) {
                                instance.onClickRadio(split, i, instance.colorBackground2, instance.colorScore);
                                sp.selectedAnswer = i;
                                instance.selectAnswer(split);
                                instance.updateRanks();
                                sp.isWaitingContinue = true;
                            }
                        }
                    }
                }

                // Check for the "Enter".
                if (event.key === 'Enter') {
                    let split = -1;
                    switch (event.location) {
                        case KeyboardEvent.DOM_KEY_LOCATION_STANDARD:
                            split = 0;
                            break;
                        case KeyboardEvent.DOM_KEY_LOCATION_NUMPAD:
                            split = 1;
                            break;
                    }

                    if (split >= 0 && split < instance.splits.length) {
                        const sp = instance.splits[split];
                        if (sp.isWaitingContinue && sp.buttonNext !== undefined) {
                            instance.continueAnswer(split);
                        }
                    }
                }
            });
        }

        createQuestionTip(sp) {
            if (sp.questionTip !== undefined || sp.attempts[0].querytip.length === 0) {
                return;
            }

            const height = Math.round(2 * sp.definitionHeight / 3);
            const heightTip = sp.definitionHeight - height - this.padding;
            const top = sp.definitionTop;

            sp.questionTip = this.createDOMElement('div', {
                parent: sp.parent,
                classnames: 'mmogame-quiz-aduelsplit-questiontip',
                styles: {
                    position: 'absolute',
                    left: `${this.padding}px`,
                    top: `${top + height + this.padding}px`,
                    width: `${sp.definitionWidth}px`,
                    height: `${heightTip}px`,
                    overflow: 'hidden',
                    background: this.colorTip,
                    color:  this.getContrastingColor(this.colorDefinition),
                    padding: `${this.padding}px`,
                }
            });
            sp.questionTip.innerHTML = sp.attempts[0].querytip;

            this.autoResizeText(sp.definition, sp.definitionWidth - 2 * this.padding, height, true, 0, sp.fontSize);
            this.autoResizeText(sp.questionTip, sp.definitionWidth - 2 * this.padding, heightTip, true, 0, sp.fontSize);

            sp.definitionHeightOld = sp.definitionHeight;
            sp.definition.style.height = `${height}px`;
        }

        createDivWizard(sp, left) {
            return this.createDOMElement('img', {
                classname: `mmogame-quiz-wizard`,
                parent: sp.parent,
                styles: {
                    position: 'absolute',
                    left: `${left}px`,
                    top: `${this.padding}px`,
                    height: `${this.iconSize}px`,
                    maxWidth: `${this.iconSize}px`,
                    transform: 'translateX(-50%)',
                },
                attributes: {
                    src: 'assets/wizard.svg',
                    alt: this.getStringM('js_help'),
                    role: 'button',
                },
            });
        }

        createDivSkip(sp, left) {
            return this.createDOMElement('img', {
                classname: `mmogame-quiz-skip`,
                parent: sp.parent,
                styles: {
                    position: 'absolute',
                    left: `${left}px`,
                    top: `${this.padding}px`,
                    height: `${this.iconSize}px`,
                    maxWidth: `${this.iconSize}px`,
                    transform: 'translateX(-50%)',
                },
                attributes: {
                    src: 'assets/skip.svg',
                    alt: this.getStringM('js_help'),
                    role: 'button',
                },
            });
        }

        createDivIdea(sp, left) {
            return this.createDOMElement('img', {
                classname: `mmogame-quiz-idea`,
                parent: sp.parent,
                styles: {
                    position: 'absolute',
                    left: `${left}px`,
                    top: `${this.padding}px`,
                    height: `${this.iconSize}px`,
                    maxWidth: `${this.iconSize}px`,
                    transform: 'translateX(-50%)',
                },
                attributes: {
                    src: 'assets/idea.svg',
                    alt: this.getStringM('js_help'),
                    role: 'button',
                },
            });
        }

        showCursor(sp, set) {
            if (sp === undefined || sp.parent === undefined) {
                return;
            }
            // Shows hourglass.
            for (const child of sp.parent.querySelectorAll('*')) {
                if (set) {
                    child.style.setProperty('cursor', 'wait', 'important');
                } else {
                    child.style.setProperty('cursor', '');
                }
            }
        }

        showIdea(spidea, querydefinitions, queryanswerids0, querytips, answertexts) {
            let sp = this.splits[spidea];
            sp.server = [];
            Array.from(sp.parent.children).forEach(child => {
                if (child !== sp.avatar && child !== sp.idea
                    && child !== sp.player.divMain && child !== sp.player.lblRank && child !== sp.player.lblScore
                    && child !== sp.percent.divMain && child !== sp.percent.lblRank && child !== sp.percent.lblScore
                ) {
                    child.style.visibility = 'hidden';
                }
            });

            const width = this.split.width - this.padding;
            const height = this.split.height - this.iconSize - 3 * this.padding;
            sp.ideadiv = this.createDOMElement('div', {
                parent: sp.parent,
                classnames: 'mmogame-quiz-aduelsplit-idea',
                styles: {
                    position: 'absolute',
                    left: `${this.padding}px`,
                    top: `${this.iconSize + 2 * this.padding}px`,
                    width: `${width}px`,
                    height: `${height}px`,
                    overflowX: 'auto',
                    overflowY: 'auto',
                    background: this.colorDefinition,
                    color:  this.getContrastingColor(this.colorDefinition),
                    padding: `${this.padding}px`,
                }
            });

            let s = '';
            let pos = 0;
            querydefinitions.forEach((definition, i) => {
                let q = this.formatText(definition, (i + 1) + '. ') + '<ul>';
                const ids = queryanswerids0[i].split(',');
                ids.forEach((id, j) => {
                    q += '<li>' + this.formatText(answertexts[pos], j === 0 ? "✔ " : '') + '</li>';
                    pos++;
                });
                if (querytips[i] !== '') {
                    q += querytips[i];
                }
                s += q + '</ul>';
            });
            sp.ideadiv.innerHTML = s;

            sp.idea.removeEventListener('click', sp.ideaCallback);
            const callback = () => {
                sp.ideadiv.remove();
                sp.parent.children.forEach(child => {
                    child.style.visibility = 'visible';
                });
                sp.idea.removeEventListener('click', callback);
                sp.idea.addEventListener('click', sp.ideaCallback);
            };
            sp.idea.addEventListener('click', callback);
        }

        createDifficultyRing(parent, left, top, size) {
            if (!parent || !(parent instanceof HTMLElement)) {
                throw new Error("createDifficultyRing: parent must be a valid HTMLElement");
            }

            // ---- Create container ----
            const el = document.createElement("div");
            el.style.position = "absolute";
            el.style.left = `${left}px`;
            el.style.top = `${top}px`;
            el.style.width = `${size}px`;
            el.style.height = `${size}px`;
            el.style.pointerEvents = "none"; // Purely decorative
            parent.appendChild(el);

            // ---- Create SVG ----
            const svgNS = "http://www.w3.org/2000/svg";
            const svg = document.createElementNS(svgNS, "svg");
            svg.setAttribute("width", "100%");
            svg.setAttribute("height", "100%");
            svg.setAttribute("viewBox", "0 0 120 120");
            svg.setAttribute("aria-hidden", "true");
            el.appendChild(svg);

            const r = 56;
            const startDeg = 120; // Hour 7.

            // Foreground ring (difficulty)
            const fg = document.createElementNS(svgNS, "circle");
            fg.setAttribute("cx", "60");
            fg.setAttribute("cy", "60");
            fg.setAttribute("r", String(r));
            fg.setAttribute("fill", "none");
            fg.setAttribute("stroke", 'rgba(255,255,255,0.35)');
            fg.setAttribute("stroke-width", '10');
            fg.setAttribute("stroke-linecap", "round");
            fg.style.transformOrigin = "60px 60px";
            fg.style.transform = `rotate(${startDeg}deg)`;

            svg.appendChild(fg);

            return {el: el, fg: fg, size: size};
        }

        updateDifficultyRing(ring, percent) {
            const sweepDeg = 300; // Hour 5.

            const r = 56;
            const C = 2 * Math.PI * r;
            const sweepLen = C * (sweepDeg / 360);

            ring.fg.style.strokeDasharray = `${percent * sweepLen} ${C}`;
        }

        getMinIconSize(countquestion) {
            let size1 = 0;
            let size2;
            if (countquestion > 0) {
                const el = document.createElement("div");
                el.style.position = "absolute";
                el.style.left = "100px";
                el.style.top = "100px";
                el.style.whiteSpace = "nowrap";
                el.style.width = `1em`;
                el.style.height = `1em`;
                el.style.margin = "0";
                el.style.border = "0";
                el.innerHTML = countquestion + " / " + countquestion;
                this.body.appendChild(el);

                size1 = Math.round(1 / 0.8 * Math.max(el.scrollWidth, el.scrollHeight));
            }

            const tempDiv = document.createElement("div");
            tempDiv.style.visibility = "hidden";
            tempDiv.style.whiteSpace = "nowrap";
            this.body.appendChild(tempDiv);
            tempDiv.style.width = `1em`;
            tempDiv.style.height = `1em`;
            tempDiv.innerHTML = '#9999';
            size2 = Math.max(tempDiv.scrollWidth, tempDiv.scrollHeight);
            this.body.removeChild(tempDiv);

            return Math.round(1.1 * Math.max(size1, size2));
        }

        showHelpScreen(div) {
            div.innerHTML = `
                <br>
                <div>${this.getStringT('js_alone_help')}</div><br>

                <table class="mmogame-table-help">
                    <tr>
                        <td><img height="83" src="type/quiz/assets/aduel/example2.png" alt="" /></td>
                        <td>${this.getStringT('js_aduel_example2')}</td>
                    </tr>
                </table>
            `;
        }

        getStringT(name) {
            return M.util.get_string(name, 'mmogametype_quiz');
        }
    };
});