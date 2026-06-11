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

// mmogame/type/quiz/js/mmogametypequizsplit.js

/**
 * Defines Split mode behavior for quiz games.
 *
 * @package
 */

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

            if (modelparams !== null) {
                const params = JSON.parse(modelparams);
                if (params.splitx !== undefined && params.splitx > 1) {
                    splitx = params.splitx;
                }
                if (params.splity !== undefined && params.splity > 1) {
                    splity = params.splity;
                }
            }
            this.countX = splitx;
            this.countY = splity;

            this.countAll = this.countX * this.countY;
            this.computeSizes(0);

            super.gateOpen(mmogameid, pin, kinduser, user, splitx, splity);

            window.addEventListener(
                "gamepadconnected",
                function() {
                    requestAnimationFrame(
                        function() {
                            this.updateGamepads();
                        }.bind(this)
                    );
                }.bind(this)
            );
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
            const csplits = this.splits.length;
            for (let i = 0; i < csplits; i++) {
                const sp = this.splits[i];
                avatarids.push(sp.avatarid);
                this.avatarfiles.push(info.avatars[this.gategetavatar(i, sp.avatarpos)]);
            }

            require(['core/ajax'], function(Ajax) {
                // Defining the parameters to be passed to the service.
                let params = {
                    sessionkeys: this.sessionkeys.join(','),
                    avatarids: avatarids.join(','),
                };
                // Calling the service through the Moodle AJAX API.
                let getAttemptsSplit = Ajax.call([{
                    methodname: 'mmogametype_quiz_get_attempts_split',
                    args: params
                }]);

                // Handling the response.
                getAttemptsSplit[0].done(function(response) {
                    const avatars = response.avatars;
                    const attemptkeys = response.attemptkeys;
                    const attemptqueryids = response.attemptqueryids;
                    const querydefinitions = response.querydefinitions;
                    const querytips = response.querytips;
                    const queryanswerids = response.queryanswerids;
                    const numattempts = response.numattempts;
                    const answertexts = response.answertexts;
                    const queryanswerids0 = response.queryanswerids0;
                    const grades = response.grades;
                    const countqueries = response.countqueries;
                    const countmastered = response.countmastered;
                    const islastcorrect = response.islastcorrect;
                    const ranks = response.ranks;
                    const queryranks = response.queryranks;
                    const hasidea = response.hasidea;
                    const state = response.state;
                    const statetime = response.statetime;
                    this.info = {
                        avatars: avatars,
                        attemptkeys: attemptkeys,
                        attemptqueryids: attemptqueryids,
                        numattempts: numattempts,
                        querydefinitions: querydefinitions,
                        querytips: querytips,
                        queryanswerids: queryanswerids,
                        answertexts: answertexts,
                        queryanswerids0: queryanswerids0,
                        grades: grades,
                        countqueries: countqueries,
                        countmastered: countmastered,
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
                        setTimeout(function() {
                            this.callGetAttemptsSplit();
                        }.bind(this), 10000);
                        return;
                    }
                    if (this.info.statetime !== 0) {
                        setTimeout(function() {
                            this.sendGetAttemptsSplit();
                        }.bind(this), 1000 * this.info.statetime);
                    }
                    this.computeSizes(0, this.getMinIconSize(this.info.countqueries));
                    this.createScreen();
                }.bind(this)).fail(
                    function(error) {
                        this.showError("mmogametypequizsplit.getattemptssplit");
                        return error;
                    }.bind(this)
                );
            }.bind(this));
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
                split.grade = 0;
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
                    this.updateGrade(sp);
                    this.updatePercent(sp);
                    i++;
                }
            }

            this.createScreenKeyboard();
        }

        createScreenSplit(split, iX, iY) {
            const sp = this.splits[split];
            const left = iX * (this.split.width + this.padding);
            const top = this.split.offsetY + iY * (this.split.height + this.padding);
            sp.parent = this.createDOMElement(
                'div',
                {
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
                }
            );

            const ishorizontal = this.split.width >= this.split.height;
            sp.player = this.createDivGradePercent(sp.parent, this.iconSize + 2 * this.padding, true);
            sp.percent = this.createDivGradePercent(sp.parent, 2 * this.iconSize + 3 * this.padding, false);
            this.computeFontGradePercent(sp);

            sp.ring = this.createDifficultyRing(sp.parent, 2 * this.iconSize + 3 * this.padding, this.padding, this.iconSize);
            if (this.info.countqueries > 0) {
                let percent = sp.attempts[0].queryrank / this.info.countqueries;
                this.updateDifficultyRing(sp.ring, percent);
            }
            // Avatar.
            const leftAvatar = Math.round(this.padding + this.iconSize / 2);
            sp.avatar = this.createDOMElement(
                'img',
                {
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
                }
            );

            sp.wizard = this.createDivWizard(sp, 3 * this.iconSize + this.iconSize / 2 + 4 * this.padding);
            sp.wizard.addEventListener(
                "click",
                function() {
                    this.createQuestionTip(sp);
                }.bind(this)
            );

            sp.skip = this.createDivSkip(sp, 4 * this.iconSize + this.iconSize / 2 + 5 * this.padding);
            sp.skip.addEventListener(
                "click",
                function() {
                    this.sendAnswer(split, true, false, true);
                    this.updateRanks();
                    // Remove the question that is used.
                    sp.attempts.shift();
                    this.showNextQuestion(sp.position);
                }.bind(this)
            );
            if (this.info.hasidea) {
                sp.idea = this.createDivIdea(sp, 5 * this.iconSize + this.iconSize / 2 + 6 * this.padding);
                sp.ideaCallback = function() {
                    this.sendAnswer(split, true, false, false, true);
                    this.askNextQuestions();
                }.bind(this);
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
            sp.definition = this.createDOMElement(
                'div',
                {
                    parent: sp.parent,
                    classnames: 'mmogame-quiz-split-definition',
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
                }
            );
            sp.definition.innerHTML = this.sanitizeFormattingHtml(
                this.formatText(sp.attempts[0].definition, sp.attempts[0].numattempt + ". ")
            );

            sp.definition.addEventListener(
                'click',
                function() {
                    this.continueAnswer(split);
                }.bind(this)
            );

            sp.answersLeft = ishorizontal ? sp.definitionWidth + 2 * this.padding : this.padding;
            sp.answersWidth = this.split.width - sp.answersLeft;
            sp.answersTop = ishorizontal ?
                this.iconSize + 2 * this.padding :
                sp.definitionTop + sp.definitionHeight + 2 * this.padding;
            sp.answersHeight = this.split.height - sp.answersTop - 2 * this.padding;

            this.createAnswerMultichoice(
                sp.parent,
                split,
                sp
            );

            this.computeFontSize(split, sp);

            this.endofgame = false;
        }

        setColors(colors) {
            super.setColors(colors);

            this.colorDefinition = this.getColorHex(colors[1]);
            this.colorTip = this.getColorHex(colors[2]);
            this.colorGrade = colors[3];
        }

        /**
         * Creates multiple-choice answer options.
         *
         * @param {object} parent
         * @param {number} split
         * @param {object} sp
         * @return void
         */
        createAnswerMultichoice(parent, split, sp) {
            const attempt = sp.attempts[0];
            const n = attempt.answers.length;
            sp.aItemRadio = Array(n);
            sp.aItemLabel = Array(n);

            let aRandom = this.computeRandom(n);

            // Iterate over each answer.
            for (let i = 0; i < n; i++) {
                this.createAnswerMultichoiceItem(split, sp, i, aRandom, attempt);
            }

            sp.selectedAnswer = -1;
            sp.aRandom = aRandom;
            sp.timestart = Math.round(Math.floor(Date.now() / 1000));
        }

        createAnswerMultichoiceItem(split, sp, i, aRandom, attempt) {
            const ans = aRandom[i];
            const label = this.createDOMElement(
                'label',
                {
                    parent: sp.parent,
                    classnames: 'mmogame-quiz-split-label' + i,
                    styles: {
                        position: 'absolute',
                        color: this.getContrastingColor(this.colorBackground),
                        align: "left",
                        marginTop: 0,
                        marginBottom: 0,
                        overflow: 'visible',
                        lineHeight: 'normal',
                    }
                }
            );
            label.innerHTML = this.sanitizeFormattingHtml(
                this.formatText(attempt.answers[ans], (i + 1) + ". ")
            );
            label.htmlFor = "mmogame_quiz_split-input" + i;

            // Create the checkbox.
            const checked = false;
            const item = this.createRadiobox(sp.parent, 0, this.colorBackground, this.colorGrade, checked);
            item.style.position = "absolute";
            item.style.left = sp.answersLeft + "px";
            item.id = "mmogame_quiz_input" + i;

            // Event listeners for interactions.
            item.addEventListener(
                'click',
                function() {
                    if (!item.classList.contains("disabled")) {
                        this.onClickRadio(split, i, this.colorBackground2, this.colorGrade);
                        sp.selectedAnswer = i;
                        this.selectAnswer(split);
                        this.updateRanks();
                    }
                }.bind(this)
            );

            label.addEventListener(
                'click',
                function() {
                    if (this.splits[split].stateShowCorrect !== undefined) {
                        return; // Disabled.
                    }
                    this.onClickRadio(split, i, this.colorBackground2, this.colorGrade);
                    sp.selectedAnswer = i;
                    this.selectAnswer(split);
                    this.updateRanks();
                }.bind(this)
            );

            sp.aItemRadio[i] = item;
            sp.aItemLabel[i] = label;
        }

        computeRandom(n) {
            // Create an array with numbers from 0 to n.
            let aRandom = Array.from(
                {length: n},
                function(value, i) {
                    return i;
                }
            );
            // Shuffle the array using Fisher-Yates algorithm.
            for (let i = n - 1; i > 0; i--) {
                let j = Math.floor(Math.random() * (i + 1)); // Generate a random index.
                [aRandom[i], aRandom[j]] = [aRandom[j], aRandom[i]]; // Swap elements.
            }
            return aRandom;
        }

        computeFontSize(split, sp) {
            const ishorizontal = this.split.width >= this.split.height;
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
            // Binary search for the largest font size that still fits.
            while ((maxSize - minSize) > precision) {
                const fontSize = (minSize + maxSize) / 2;
                style.fontSize = fontSize + "px";

                // Check whether the definition overflows.
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

            // Apply final font size.
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
            if (top + this.iconSize + this.padding + n * space < this.split.height) {
                for (let i = 1; i < n; i++) {
                    const elem = sp.aItemLabel[i];
                    const newTop = parseInt(elem.style.top) + i * sp.fontSize;
                    elem.style.top = `${newTop}px`;
                    sp.aItemRadio[i].style.top = `${newTop}px`;
                }
                top += (n - 1) * space;
            }

            if (ishorizontal) {
                // If there is enough vertical space, expand the definition block height.
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
            const numattempts = info.numattempts[splitInfo].split(",");
            let attemptkeys = info.attemptkeys[splitInfo].split(",");
            sp.attempts = [];
            sp.grade = parseInt(info.grades[splitInfo]);
            sp.rank = parseInt(info.ranks[splitInfo]);
            sp.countmastered = info.countmastered[splitInfo];
            const na = attemptkeys.length;
            for (let i = 0; i < na; i++) {
                let item = {attemptkey: attemptkeys[i]};
                item.queryid = queryids[i];
                item.islastcorrect = info.islastcorrect[item.queryid];
                item.definition = info.querydefinitions[item.queryid];
                item.querytip = info.querytips[item.queryid];
                item.answerids = info.queryanswerids[item.queryid].split(",");
                item.answerids0 = info.queryanswerids0[item.queryid].split(",");
                item.answers = [];
                item.numattempt = numattempts[i];
                item.queryrank = info.queryranks[item.queryid];
                const n = item.answerids.length;
                for (let j = 0; j < n; j++) {
                    item.answers.push(info.answertexts[item.answerids[j]]);
                }

                sp.attempts.push(item);
            }
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

            this.onClickRadio(split, sp.selectedAnswer, this.colorBackground2, this.colorGrade);
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

            // Update the selected radio button and deselect others.
            sp.aItemRadio.forEach(
                function(item, i) {
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
                }.bind(this)
            );
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
                            this.onClickRadio(split, i, this.colorBackground2, this.colorGrade);
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
                    const addgrade = isWizard ? 1 : sp.aItemRadio.length - 1;
                    sp.grade += addgrade;
                    if (sp.attempts[0].islastcorrect === 0) {
                        sp.countmastered++;
                        this.updatePercent(sp);
                    }
                    this.updateGrade(sp);
                    this.showCorrect(sp, true, addgrade, 0);
                    this.sendAnswer(split, true, isWizard, false);
                } else {
                    // Wrong answer.
                    let addgrade = -Math.min(sp.grade, 1);
                    sp.grade += addgrade;
                    this.updateGrade(sp);

                    this.showCorrect(sp, false, addgrade);
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
            const csplits = this.splits.length;
            if (csplits === 1) {
                return;
            }
            for (let i = 0; i < csplits; i++) {
                this.splits[i].rank = 0;
            }
            let rank = 1;
            for (;;) {
                let found = false;
                let max = -1;
                for (let i = 0; i < csplits; i++) {
                    const sp = this.splits[i];
                    if (sp.rank === 0) {
                        found = true;
                        if (sp.grade > max) {
                            max = sp.grade;
                        }
                    }
                }
                if (!found) {
                    break; // Finished.
                }
                let count = 0;
                for (let i = 0; i < csplits; i++) {
                    const sp = this.splits[i];
                    if (sp.grade === max) {
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
            sp.player.lblRank.textContent = '#' + sp.rank;
            sp.rankcache = sp.rank;
        }

        updateGrade(sp) {
            sp.player.lblGrade.textContent = sp.grade;
            sp.player.lblRank.textContent = sp.rank !== '' ? '#' + sp.rank : '';
        }

        updatePercent(sp) {
            sp.percent.lblGrade.textContent = this.info.countqueries > 0 ? sp.countmastered + " / " + this.info.countqueries : '';
            this.autoResizeText(sp.percent.lblGrade, 0.8 * this.iconSize, sp.percent.cellSize,
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
                attemptkey: attempt.attemptkey,
                iscorrect: iscorrect,
                answer: attempt.answerids0[pos],
                tools: tools,
                timestart: sp.timestart,
                timeanswer: Math.round(Math.floor(Date.now() / 1000)),
            };
            sp.server.push(info);
        }

        showCorrect(sp, iscorrect, addgrade) {
            const correctPos = sp.aRandom.indexOf(0);
            const topCorrect = sp.aItemLabelTop[0];
            const topIcon = topCorrect + this.padding + sp.aItemLabel[correctPos].offsetHeight;
            const topIncorrect = topIcon + this.iconSize + this.padding;
            const leftLabel = parseFloat(sp.aItemRadio[0].style.left);

            sp.stateShowCorrect = true;
            // Move correct answer to position 0.
            const n = sp.aItemRadio.length;
            for (let i = 0; i < n; i++) {
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
                    this.durationAnination
                );
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

            sp.timeoutStrip = setTimeout(function() {
                sp.timeoutStrip = undefined;

                if (sp.stateShowCorrect === undefined) {
                    return; // We are in next question.
                }

                this.createQuestionTip(sp);
                this.showCorrectStrip(sp, topIcon, iscorrect, addgrade);
                this.createNextButton(sp, correctPos, topIncorrect);

                sp.aItemLabel[correctPos].style.opacity = "1";
                sp.aItemLabel[correctPos].style.display = "block";
                sp.aItemLabel[sp.selectedAnswer].style.opacity = "1";
                sp.aItemLabel[sp.selectedAnswer].style.display = "block";
            }.bind(this), this.durationAnination);
        }

        createCorrectIcon(sp, parent, left, topSource, topDestination, sizeRadio, iscorrect) {
            const div = this.createDiv(
                parent,
                'mmogame-quiz-split-iscorrect',
                left,
                topSource,
                sizeRadio,
                sizeRadio);
            div.innerHTML = this.getSVGcorrect(sizeRadio, iscorrect, this.colorGrade, this.colorGrade);
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
                    opacity -= 0.05;
                    element.style.opacity = opacity;
                }
            }, duration / 20);
        }

        moveElementSmoothly(sp, element, finalY, duration) {
            const startY = element.offsetTop;
            const deltaY = finalY - startY;
            const startTime = performance.now();

            const animate = function() {
                if (sp.stateShowCorrect === undefined) {
                    return;
                }
                const currentTime = performance.now();
                const elapsedTime = currentTime - startTime;

                const progress = Math.min(elapsedTime / duration, 1);

                const currentY = startY + deltaY * progress;
                element.style.top = `${currentY}px`;

                if (progress < 1) {
                    requestAnimationFrame(animate);
                } else {
                    setTimeout(function() {
                        element.style.display = "block";
                    }, 500);
                }
            };

            animate();
        }

        hideQuestion(sp) {
            this.removeCorrectIcons(sp);

            const n = sp.aItemLabel.length;
            for (let i = 0; i < n; i++) {
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
            const n2 = sp.aItemLabel.length;
            for (let i = 0; i < n2; i++) {
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
                this.drawRadio(radio, 0xFFFFFF, this.colorGrade);
                sp.selectedAnswer = -1;
            }

            if (sp.attempts.length === 0) {
                sp.definition.textContent = '';
                const n = sp.aItemLabel.length;
                for (let i = 0; i < n; i++) {
                    sp.aItemLabel[i].textContent = '';
                }
                this.hideQuestion(sp);

                this.askNextQuestions(sp);
                return;
            }

            // Shows the next question.
            sp.definition.innerHTML = this.sanitizeFormattingHtml(
                this.formatText(sp.attempts[0].definition, sp.attempts[0].numattempt + ". ")
            );
            this.autoResizeText(sp.definition, sp.definitionWidth, sp.definitionHeight, true, 0, 0);
            const attempt = sp.attempts[0];
            this.makeItemLength(split, sp, attempt.answers.length);
            const n3 = sp.aItemLabel.length;
            for (let i = 0; i < n3; i++) {
                const ans = sp.aRandom[i];
                sp.aItemLabel[i].innerHTML = this.sanitizeFormattingHtml(
                    this.formatText(attempt.answers[ans], (i + 1) + ": ")
                );
            }

            this.computeFontSize(split, sp);

            if (this.info.countqueries > 0) {
                let percent = sp.attempts[0].queryrank / this.info.countqueries;
                this.updateDifficultyRing(sp.ring, percent);
            }
        }

        makeItemLength(split, sp, n) {
            let n1 = sp.aItemLabel.length;
            while (n < n1) {
                sp.parent.removeChild(sp.aItemLabel[n]);
                sp.parent.removeChild(sp.aItemRadio[n]);
                sp.aItemLabel.splice(n, 1);
                sp.aItemRadio.splice(n, 1);
                n1 = sp.aItemLabel.length;
            }

            let n2 = sp.aItemLabel.length;
            while (n2 < n) {
                this.createAnswerMultichoiceItem(split, sp, sp.aItemLabel.length, sp.aRandom, sp.attempts[0]);
                n2 = sp.aItemLabel.length;
            }
        }

        askNextQuestions(sp) {
            const split = sp.position;
            this.showCursor(sp, true);

            let sessionkeys = [];
            let attemptkeys = [];
            let answers = [];
            let timestarts = [];
            let timeanswers = [];
            let tools = [];

            let spidea = -1;
            const n = this.splits.length;
            for (let i = 0; i < n; i++) {
                const sp2 = this.splits[i];
                const sessionkey = this.sessionkeys[i];
                const n2 = sp2.server.length;
                for (let j = 0; j < n2; j++) {
                    const temp = sp2.server[j];
                    sessionkeys.push(sessionkey);
                    attemptkeys.push(temp.attemptkey);
                    answers.push(temp.answer);
                    timestarts.push(temp.timestart);
                    timeanswers.push(temp.timeanswer);
                    tools.push(temp.tools);
                    if (temp.tools === 4) {
                        spidea = i;
                    }
                }
            }

            require(['core/ajax'], function(Ajax) {
                // Defining the parameters to be passed to the service
                let params = {
                    sessionkeys: sessionkeys.join(','),
                    attemptkeys: attemptkeys.join(','),
                    answers: answers.join(','),
                    timestarts: timestarts.join(','),
                    timeanswers: timeanswers.join(','),
                    returnsessionkey: this.sessionkeys[sp.position],
                    tools: tools.join(','),
                };
                // Calling the service through the Moodle AJAX API
                let sendAnswers = Ajax.call([{
                    methodname: 'mmogametype_quiz_send_answers_split',
                    args: params
                }]);
                // Handling the response
                sendAnswers[0].done(function(response) {
                    const avatars = response.avatars;
                    const attemptkeys = response.attemptkeys;
                    const attemptqueryids = response.attemptqueryids;
                    const numattempts = response.numattempts;
                    const querydefinitions = response.querydefinitions;
                    const querytips = response.querytips;
                    const queryanswerids = response.queryanswerids;
                    const answertexts = response.answertexts;
                    const queryanswerids0 = response.queryanswerids0;
                    const grades = response.grades;
                    const countqueries = response.countqueries;
                    const countmastered = response.countmastered;
                    const islastcorrect = response.islastcorrect;
                    const ranks = response.ranks;
                    const queryranks = response.queryranks;
                    const state = response.state;
                    const statetime = response.statetime;
                    if (spidea >= 0) {
                        this.showIdea(spidea, querydefinitions, queryanswerids0, querytips, answertexts);
                        return;
                    }

                    this.info = {
                        avatars: avatars,
                        attemptkeys: attemptkeys,
                        attemptqueryids: attemptqueryids,
                        numattempts: numattempts,
                        querydefinitions: querydefinitions,
                        querytips: querytips,
                        queryanswerids: queryanswerids,
                        answertexts: answertexts,
                        queryanswerids0: queryanswerids0,
                        grades: grades,
                        countqueries: countqueries,
                        countmastered: countmastered,
                        islastcorrect: islastcorrect,
                        ranks: ranks,
                        queryranks: queryranks,
                        state: state,
                        statetime: statetime,
                    };
                    this.removeFromServer(attemptkeys);
                    if (this.info.state === 0) {
                        this.createDivMessageStart(this.getStringM('js_wait_to_start'));
                        setTimeout(function() {
                            this.callGetAttemptsSplit();
                        }.bind(this), 15000);
                        return;
                    }

                    this.copySplitData(0, split);
                    this.showNextQuestion(split);
                    this.updatePercent(this.splits[split]);

                    this.showCursor(sp, false);
                }.bind(this)).fail(
                    function(error) {
                        this.showError("mmogametypequizsplit.asknextquestion");
                        this.showCursor(sp, false);
                        return error;
                    }.bind(this)
                );
            }.bind(this));
        }

        computeFontGradePercent(sp) {
            if (sp.position === 0) {
                const player = sp.player;
                player.lblGrade.textContent = "99999";
                this.autoResizeText(player.lblGrade, this.iconSize, player.cellSize, false, 0, 0);
                const fontSize = player.lblGrade.style.fontSize;
                const lineHeight = Math.round(parseFloat(fontSize));
                if (player.lblRank !== undefined) {
                    player.lblRank.style.fontSize = fontSize;
                    player.lblRank.lineHeight = lineHeight;
                }
                player.lblGrade.lineHeight = lineHeight;
                player.lblGrade.textContent = "";
            } else {
                const player = sp.player;
                let fontSize = this.splits[0].player.lblRank.style.fontSize;
                const lineHeight = Math.round(parseFloat(fontSize));
                if (player.lblRank !== undefined) {
                    player.lblRank.style.fontSize = fontSize;
                    player.lblRank.lineHeight = lineHeight;
                }
                player.lblGrade.style.fontSize = fontSize;
                player.lblGrade.lineHeight = lineHeight;
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

        createAddGrade(sp, left, iconSize, addgrade) {
            if (addgrade === 0) {
                return false;
            }

            if (addgrade > 0) {
                addgrade = "+" + addgrade;
            }

            const label = this.createDOMElement(
                'div',
                {
                    parent: sp.stripCorrect,
                    classnames: `mmogame-quiz-addgrade`,
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
                        title: this.getStringM('js_add_grade'),
                    },
                }
            );

            label.textContent = addgrade;

            return label;
        }

        showCorrectStrip(sp, top, iscorrect, addgrade) {
            const iconSize = Math.max(1, Math.min(this.iconSize, Math.ceil(sp.answersWidth / 4 - this.padding - this.padding / 4)));
            sp.stripCorrect = this.createDOMElement(
                'div',
                {
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
                }
            );

            this.createAddGrade(sp, 0, iconSize, addgrade);

            this.createDOMElement(
                'img',
                {
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
                }
            );
            this.createCorrectIcon(sp, sp.stripCorrect, iconSize + this.padding, 0, 0, this.iconSize, iscorrect);
        }

        async createNextButton(sp, correctPos, topIncorrect) {
            const time = correctPos === sp.selectedAnswer ?
                this.timeBeforeShowNextButtonCorrect : this.timeBeforeShowNextButtonError;
            sp.aItemLabel[sp.selectedAnswer].style.display = 'block';
            setTimeout(
                async function() {
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
                    sp.buttonNext = this.createImageButton(
                        sp.parent,
                        'mmogame-quiz-next',
                        left,
                        top,
                        0,
                        iconSize,
                        'assets/next.svg'
                    );
                    sp.buttonNext.title = this.getStringM('js_next_question');
                    sp.buttonNext.addEventListener(
                        "click",
                        function() {
                            this.continueAnswer(sp.position);
                        }.bind(this)
                    );
                }.bind(this),
                time
            );
        }

        waitForHeight(el, maxRetries = 10, delay = 50, fallbackHeight = 24) {
            return new Promise(
                function(resolve) {
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
                }
            );
        }

        removeFromServer(attemptkeys) {
            const ns = this.splits.length;
            for (let i = 0; i < ns; i++) {
                const sp = this.splits[i];
                let j = 0;
                let n = sp.server.length;
                while (j < n) {
                    const server = sp.server[j];
                    const index = attemptkeys.indexOf(server.attemptkey);
                    if (index !== -1) {
                        sp.server.splice(j, 1);
                        attemptkeys.splice(index, 1);
                    } else {
                        j++;
                    }
                    n = sp.server.length;
                }
            }
        }

        createScreenKeyboard() {
            let instance = this;
            document.addEventListener(
                'keydown',
                function(event) {
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
                                    instance.onClickRadio(split, i, instance.colorBackground2, instance.colorGrade);
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
                }
            );
        }

        createQuestionTip(sp) {
            if (sp.questionTip !== undefined || sp.attempts[0].querytip.length === 0) {
                return;
            }

            const height = Math.round(2 * sp.definitionHeight / 3);
            const heightTip = sp.definitionHeight - height - this.padding;
            const top = sp.definitionTop;

            sp.questionTip = this.createDOMElement(
                'div',
                {
                    parent: sp.parent,
                    classnames: 'mmogame-quiz-split-questiontip',
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
                }
            );
            sp.questionTip.innerHTML = this.sanitizeFormattingHtml(sp.attempts[0].querytip);

            this.autoResizeText(sp.definition, sp.definitionWidth - 2 * this.padding, height, true, 0, sp.fontSize);
            this.autoResizeText(sp.questionTip, sp.definitionWidth - 2 * this.padding, heightTip, true, 0, sp.fontSize);

            sp.definitionHeightOld = sp.definitionHeight;
            sp.definition.style.height = `${height}px`;
        }

        createDivWizard(sp, left) {
            return this.createDOMElement(
                'img',
                {
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
                }
            );
        }

        createDivSkip(sp, left) {
            return this.createDOMElement(
                'img',
                {
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
                }
            );
        }

        createDivIdea(sp, left) {
            return this.createDOMElement(
                'img',
                {
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
                }
            );
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
            Array.from(sp.parent.children).forEach(
                function(child) {
                    if (child !== sp.avatar && child !== sp.idea
                        && child !== sp.player.divMain && child !== sp.player.lblRank && child !== sp.player.lblGrade
                        && child !== sp.percent.divMain && child !== sp.percent.lblRank && child !== sp.percent.lblGrade
                    ) {
                        child.style.visibility = 'hidden';
                    }
                }
            );

            const width = this.split.width - this.padding;
            const height = this.split.height - this.iconSize - 3 * this.padding;
            sp.ideadiv = this.createDOMElement(
                'div',
                {
                    parent: sp.parent,
                    classnames: 'mmogame-quiz-split-idea',
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
                }
            );

            let s = '';
            let pos = 0;
            querydefinitions.forEach(
                function(definition, i) {
                    let q = this.formatText(definition, (i + 1) + '. ') + '<ul>';
                    const ids = queryanswerids0[i].split(',');
                    ids.forEach(
                        function(id, j) {
                            q += '<li>' + this.formatText(answertexts[pos], j === 0 ? "✔ " : '') + '</li>';
                            pos++;
                        }.bind(this)
                    );
                    if (querytips[i] !== '') {
                        q += querytips[i];
                    }
                    s += q + '</ul>';
                }.bind(this)
            );
            sp.ideadiv.innerHTML = this.sanitizeFormattingHtml(s);

            sp.idea.removeEventListener('click', sp.ideaCallback);
            const callback = function() {
                sp.ideadiv.remove();
                Array.from(sp.parent.children).forEach(
                    function(child) {
                        child.style.visibility = 'visible';
                    }
                );
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
            el.style.pointerEvents = "none"; // Purely decorative.
            parent.appendChild(el);

            // ---- Creates SVG.
            const svgNS = "http://www.w3.org/2000/svg";
            const svg = document.createElementNS(svgNS, "svg");
            svg.setAttribute("width", "100%");
            svg.setAttribute("height", "100%");
            svg.setAttribute("viewBox", "0 0 120 120");
            svg.setAttribute("aria-hidden", "true");
            el.appendChild(svg);

            const r = 56;
            const startDeg = 120; // Hour 7.

            // Foreground ring (difficulty).
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

        getMinIconSize(countqueries) {
            let size1 = 0;
            let size2;
            if (countqueries > 0) {
                const el = document.createElement("div");
                el.style.position = "absolute";
                el.style.left = "100px";
                el.style.top = "100px";
                el.style.whiteSpace = "nowrap";
                el.style.width = `1em`;
                el.style.height = `1em`;
                el.style.margin = "0";
                el.style.border = "0";
                el.textContent = countqueries + " / " + countqueries;
                this.body.appendChild(el);

                size1 = Math.round(1 / 0.8 * Math.max(el.scrollWidth, el.scrollHeight));
            }

            const tempDiv = document.createElement("div");
            tempDiv.style.visibility = "hidden";
            tempDiv.style.whiteSpace = "nowrap";
            this.body.appendChild(tempDiv);
            tempDiv.style.width = `1em`;
            tempDiv.style.height = `1em`;
            tempDiv.textContent = '#9999';
            size2 = Math.max(tempDiv.scrollWidth, tempDiv.scrollHeight);
            this.body.removeChild(tempDiv);

            return Math.round(1.1 * Math.max(size1, size2));
        }

        showHelpScreen(div) {
            div.innerHTML = this.sanitizeFormattingHtml(
                `<br>
                <div>${this.getStringT('js_alone_help')}</div><br>

                <table class="mmogame-table-help">
                    <tr>
                        <td><img height="83" src="type/quiz/assets/aduel/example2.png" alt="" /></td>
                        <td>${this.getStringT('js_aduel_example2')}</td>
                    </tr>
                </table>`
            );
        }

        getStringT(name) {
            return M.util.get_string(name, 'mmogametype_quiz');
        }
    };
});