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

define(['mmogametype_quiz/mmogametypequiz'],
    function(MmoGameTypeQuiz) {
        return class MmoGameTypeQuizAduel extends MmoGameTypeQuiz {

    player1; // Stores all info for player1
    player2; // Stores all info for player2
    isWaitOpponent; // True if wait someone to answer a set of questions.

    // Timer variables.
    divTimer;
    timerTimeout;

    stripLeft; // Left position of next button.
    stripTop; // Top position of next button.

    timeoutWaitOpponent = 2000;

    // Tools button
    buttonHighScore;
    button5050;
    buttonSkip;
    buttonWizard;

    constructor() {
        super('aduel');

        // Initialize core properties
        this.cIcons = this.isVertical ? 5 : 8;

        this.isWaitOpponent = false;
    }

    createIconBar() {
        let i = 0;
        const step = this.iconSize + this.padding;

        const nicknameHeight = Math.round(this.iconSize / 3);
        const fragment = document.createDocumentFragment(); // Batch DOM updates

        let nickname, avatar;
        [nickname, avatar] = this.createNicknameAvatar(fragment, 'mmogame-quiz-aduel',
            Math.round(this.padding + (i++) * step),
            this.padding,
            2 * this.iconSize + this.padding,
            nicknameHeight,
            this.padding + nicknameHeight,
            this.iconSize);

        this.player1 = this.createDivScorePercent(fragment, 'mmogame-quiz-aduel-player1',
            this.padding + (i++) * (this.iconSize + this.padding), this.padding + nicknameHeight,
            true, this.colorScore);
        this.player1.avatarElement = avatar;
        this.player1.nicknameElement = nickname;
        this.player1.cacheAvatar = '';
        this.player1.cacheNickname = '';
        this.player1.lblScore.title = this.getStringM('js_grade');
        this.player1.lblRank.title = this.getStringM('js_position_grade');

        [nickname, avatar] = this.createNicknameAvatar(fragment, 'mmogame-quiz-alone',
            Math.round(this.padding + (i++) * step),
            this.padding,
            2 * this.iconSize + this.padding,
            nicknameHeight,
            this.padding + nicknameHeight,
            this.iconSize);

        this.player2 = this.createDivScorePercent(fragment, 'mmogame-quiz-aduel-player2',
            this.padding + (i++) * (this.iconSize + this.padding), this.padding + nicknameHeight,
            false, this.colorScore2);
        this.player2.avatarElement = avatar;
        this.player2.nicknameElement = nickname;
        this.player2.cacheAvatar = '';
        this.player2.cacheNickname = '';
        this.player2.lblScore.title = this.getStringM('js_percent_opponent');
        this.player2.lblRank.title = this.getStringM('js_position_percent');

        this.createArea(2 * this.padding + this.iconSize + nicknameHeight, this.isVertical ? this.iconSize : 0);

        this.createDivTimer(fragment, this.padding + (i++) * (this.iconSize + this.padding), this.padding + nicknameHeight,
            this.iconSize);

        if (this.isVertical) {
            i = 0;
            this.createButtonSound(fragment,
                this.padding + (i++) * (this.iconSize + this.padding), this.areaRect.top + this.areaRect.height);
        } else {
            this.createButtonSound(fragment, this.padding + (i++) * (this.iconSize + this.padding), this.padding + nicknameHeight);
        }
        // High score button and Cut tool are in the same position.
        let leftButton;
        if (this.isVertical) {
            leftButton = this.padding + (this.iconSize + this.padding);
            this.buttonHighScore = this.createImageButton(fragment, 'mmogame-quiz-highscore',
                leftButton, this.areaRect.top + this.areaRect.height,
                this.iconSize, this.iconSize, 'assets/highscore.svg');

            this.button5050 = this.createImageButton(fragment, 'mmogame-quiz-aduel-5050',
                leftButton, this.areaRect.top + this.areaRect.height, this.iconSize, this.iconSize,
                'assets/cutred.svg');
        } else {
            leftButton = this.padding + (i++) * (this.iconSize + this.padding);

            this.buttonHighScore = this.createImageButton(fragment, 'mmogame-quiz-highscore',
                leftButton, this.padding + nicknameHeight, this.iconSize, this.iconSize,
                'assets/highscore.svg');

            this.button5050 = this.createImageButton(fragment, 'mmogame-quiz-aduel-5050',
                leftButton, this.padding + nicknameHeight, this.iconSize, this.iconSize,
                'assets/cutred.svg');
        }
        this.buttonHighScore.style.visibility = 'hidden';
        this.buttonHighScore.addEventListener("click", () => {
            this.sendGetHighScore();
        });
        this.button5050.addEventListener("click", () => {
            this.callGetAttempt({tool1: 1});
        });
        this.button5050.title = this.getStringT('js_help_5050');

        leftButton += this.iconSize + this.padding;

        // Skip and wizard tools are in the same position.
        if (this.isVertical) {
            this.buttonSkip = this.createImageButton(fragment, 'mmogame-quiz-aduel-skip',
                leftButton, this.areaRect.top + this.areaRect.height, this.iconSize, this.iconSize,
                'assets/skip.svg');
        } else {
            this.buttonSkip = this.createImageButton(fragment, 'mmogame-quiz-aduel-skip',
                leftButton, this.padding + nicknameHeight, this.iconSize,
                this.iconSize, 'assets/skip.svg');
        }
        this.buttonSkip.addEventListener("click", () => {
            this.callSetAnswer("tool2");
        });
        this.buttonSkip.title = this.getStringT('js_help_skip');

        if (this.isVertical) {
            this.buttonWizard = this.createImageButton(fragment, 'mmogame-quiz-aduel-wizard',
                leftButton, this.areaRect.top + this.areaRect.height, this.iconSize, this.iconSize,
                'assets/wizard.svg');
        } else {
            this.buttonWizard = this.createImageButton(fragment, 'mmogame-quiz-aduel-wizard',
                leftButton, this.padding + nicknameHeight, this.iconSize,
                this.iconSize, 'assets/wizard.svg');
        }
        this.buttonWizard.addEventListener("click", () => {
            this.callGetAttempt({tool3: 1});
        });
        this.buttonWizard.style.visibility = 'hidden';
        this.buttonWizard.title = this.getStringT('js_wizard');
        this.buttonWizard.addEventListener("click", () => {
            this.callGetAttempt({tool3: 1});
        });

        if (this.hasHelp()) {
            if (!this.isVertical) {
                this.createButtonHelp(fragment,
                    leftButton + this.padding + (i++) * (this.iconSize + this.padding), this.padding + nicknameHeight);
            } else {
                this.createButtonHelp(fragment,
                    this.padding + 3 * (this.iconSize + this.padding), this.areaRect.top + this.areaRect.height);
            }
        }

        this.body.appendChild(fragment); // Batch insert into DOM
    }

    updateButtonTool(btn, tool) {
        btn.style.visibility = tool !== undefined && parseInt(tool) !== 0 ? "hidden" : "visible";
    }

    processGetAttempt(json) {
        super.processGetAttempt(json);
        if (this.buttonHighScore !== undefined) {
            this.buttonHighScore.style.visibility = 'hidden';
        }

        if (json.name !== undefined) {
            window.document.title = json.name;
        }
        this.correct = undefined;
        if (parseInt(json.state) === 0) {
            json.qtype = '';
            if (json.colors) {
                this.setColorsString(json.colors);
                this.createIconBar();
            }
            this.showScore(json);
            this.createDivMessageStart(this.getStringM('js_wait_to_start'));
            this.sendFastJSON(); // Send fast JSON updates
            return;
        }
        // Update game state
        this.state = parseInt(json.state);
        // Need for a change on colors.
        if (this.savedColors === undefined || this.savedColors !== json.colors) {
            this.savedColors = json.colors;
            this.colors = undefined;
        }
        // Update the window title if a name is provided
        if (json.name) {
            document.title = json.name;
        }
        if (this.player1 === undefined) {
            this.setColorsString(json.colors);
            this.createIconBar();
        }

        if (this.area === undefined) {
            this.createArea(this.areaRect.top, 0);
        }

        const nicknameWidth = 2 * this.iconSize + this.padding;
        const nicknameHeight = this.iconSize / 3;
        this.updateNicknameAvatar(this.player1, json.avatar, json.nickname, nicknameWidth, nicknameHeight);
        this.updateNicknameAvatar(this.player2, json.aduelAvatar, json.aduelNickname, nicknameWidth, nicknameHeight);

        this.updateButtonTool(this.button5050, json.tool1numattempt);
        if (json.tool3 !== undefined) {
            json.tool2numattempt = -1;
        } else {
            json.tool3numattempt = -1;
        }
        this.updateButtonTool(this.buttonSkip, json.tool2numattempt);
        this.updateButtonTool(this.buttonWizard, json.tool3numattempt);

        this.attempt = json.attempt;
        this.aduelPlayer = json.aduelPlayer;
        this.aduelScore = json.aduelScore;
        this.aduelRank = json.aduelRank;

        if (json.errorcode !== undefined && json.errorcode === 'aduel_no_rivals' || json.attempt === 0) {
            if (json.param === undefined) {
                this.showWaitOpponent();
            }
            this.hideTools();
            this.showScore(json);
            this.timeout = setTimeout(() => {
                this.callGetAttempt();
            }, this.timeoutWaitOpponent);
            return;
        }

        this.isWaitOpponent = false;

        this.qtype = json.qtype;
        if (json.qtype === 'multichoice') {
            this.answers = [];
            this.answersID = json.answerids;

            json.answers.forEach((answer, index) => {
                this.answers[index] = this.repairP(answer);
            });
        }
        this.answer = json.answer !== undefined ? json.answer : null;
        this.definition = this.repairP(json.definition);
        if (json.aduelAttempt !== undefined) {
            this.definition = json.aduelAttempt + ". " + this.definition;
        }
        this.single = json.single;
        this.errorcode = json.errorcode;

        this.createScreen(json, false);
        this.updateDivTimer();
        this.sendFastJSON();
    }

    onServerGetAttemptHideButtons() {
        this.button5050.style.visibility = 'hidden';
        this.buttonSkip.style.visibility = 'hidden';
        this.player2.avatarElement.style.visibility = 'hidden';
    }

    onTimeout() {
        this.divTimer.innerHTML = '';
        this.disableInput();

        this.answer = '';
        this.answerid = 0;
        this.callSetAnswer();

        this.createNextButton(this.areaRect.width - this.iconSize - this.padding, this.stripTop);
    }

    waitOpponent() {
        if (this.isWaitOpponent) {
            return;
        }
        this.updateNicknameAvatar(this.player2, "", "");
        this.createDivMessage('mmomgame-quiz-aduel-wait-opponent',
            this.getStringT('js_aduel_wait_opponent'));
        if (this.labelTimer !== undefined) {
            this.labelTimer.innerHTML = "";
        }
    }

    hideTools() {
        this.updateButtonTool(this.button5050, -1);
        this.updateButtonTool(this.buttonSkip, -1);
        this.updateButtonTool(this.buttonWizard, -1);
    }

    processSetAnswer(json) {
        this.correct = json.correct;

        if (json.correct !== undefined) {
            if (this.qtype === "multichoice") {
                this.onServerAnswerMultichoice(json);
            }
        }

        this.disableInput();
        this.buttonHighScore.style.visibility = 'visible';

        this.isWaitOpponent = false;

        this.showCorrectAnswer(json);
        this.createNextButton(this.areaRect.width - this.iconSize - this.padding, this.stripTop);

        this.showScore(json);

        this.timeclose = 0;

        if (this.aduelPlayer === 1) {
            if (json.attempt === 0) {
                this.timeclose = 0;
                this.waitOpponent();
            }
        }

        if (this.button5050 !== undefined) {
            this.button5050.style.visibility = 'hidden';
            this.buttonSkip.style.visibility = 'hidden';
        }
    }

    onServerAnswerMultichoice(json) {
        let foundCorrect = false;

        let aduelAnswers = json.aduelPlayer === 2 && json.aduelUseranswer !== null ? json.aduelUseranswer.split(",") : '';
        let aCorrect = json.correct.split(",");
        for (let i = 0; i < this.answersID.length; i++) {
            if (this.answersID[i] === '') {
                continue;
            }

            let label = this.aItemLabel[i];

            let iscorrect1, iscorrect2;

            let iscorrect = aCorrect.includes(this.answersID[i]);

            if (this.aItemAnswer[i].classList.contains("checked")) {
                iscorrect1 = aCorrect.includes(this.answersID[i]);
            }
            if (iscorrect1) {
                foundCorrect = true;
            }

            if (aduelAnswers.includes(this.answersID[i])) {
                iscorrect2 = aCorrect.includes(this.answersID[i]);
            }

            if (iscorrect === false && iscorrect1 === undefined && iscorrect2 === undefined) {
                continue;
            }

            let width = this.labelWidth;
            let height = this.aItemLabel[i].scrollHeight;

            let move = (iscorrect2 !== undefined ? 2 : 1) * this.radioSize;
            if (iscorrect1 === undefined && iscorrect2 === undefined) {
                move = 0;
            }
            width -= move;

            if (move !== 0) {
                label.style.left = (parseInt(label.style.left) + move) + "px";
            }
            this.aItemLabel[i].style.width = width + "px";
            this.autoResizeText(this.aItemLabel[i], width, height, true, this.minFontSize, this.maxFontSize, 0.5);

            this.onServerAnswerMultichoiceShowCorrect(i, iscorrect1, iscorrect2);
        }

        this.playAudio(foundCorrect ? this.audioYes : this.audioNo);
    }

    onServerAnswerMultichoiceShowCorrect(i, iscorrect1, iscorrect2, iscorrect) {
        if (iscorrect) {
            this.aItemLabel[i].innerHTML = '<b><u>' + this.aItemLabel[i].innerHTML + '</b></u>';
        }

        if (iscorrect1 !== undefined) {
            let t = parseInt(this.aItemAnswer[i].style.top);
            let div = this.createDiv(this.area, 'mmogame-quiz-aduel-correct-answer',
                this.aItemCorrectX[i], t, this.radioSize, this.radioSize);
            div.title = iscorrect1 ? this.getStringT('js_correct_answer') : this.getStringT('js_wrong_answer');
            div.innerHTML = this.getSVGcorrect(this.radioSize, iscorrect1, this.colorScore, this.colorScore);
        }

        if (iscorrect2 !== undefined) {
            let t = parseInt(this.aItemAnswer[i].style.top);
            let div = this.createDiv(this.area, 'mmogame-aduel-opponent-correct',
                this.aItemCorrectX[i] + this.radioSize, t, this.radioSize, this.radioSize);
            div.innerHTML = this.getSVGcorrect(this.radioSize, iscorrect2, this.colorScore2, this.colorScore2);
            div.title = iscorrect2 ? this.getStringM('js_correct_answer') : this.getStringM('js_wrong_answer');
        }
    }

    showCorrectAnswer({iscorrect2, iscorrect, tool2}) {
        this.timeclose = 0;
        this.updateDivTimer();

        this.strip = this.createDiv(this.area, 'mmogame-quiz-aduel-strip',
            this.stripLeft, this.stripTop, 2 * this.iconSize + this.padding, this.iconSize);

        if (tool2 === undefined || this.aduelPlayer === 2) {
                const btn = this.createImage(this.area, 'mmogame-quiz-aduel-player1',
                this.stripLeft + this.iconSize / 2, this.stripTop, this.iconSize,
            this.iconSize, "assets/avatars/" + this.player1.cacheAvatar);
            btn.style.transform = 'translateX(-50%)';
        }

        if (tool2 === undefined && this.aduelPlayer === 2) {
            const btn = this.createImage(this.area, 'mmogame-quiz-aduel-player2',
                this.stripLeft + this.iconSize / 2 + this.iconSize, this.stripTop, this.iconSize,
                this.iconSize, "assets/avatars/" + this.player2.cacheAvatar);
            btn.style.transform = 'translateX(-50%)';
        }

        this.strip.style.top = this.stripTop + "px";
        let s = this.getSVGcorrect(this.iconSize, iscorrect !== 0, this.colorScore, this.colorScore);
        if (tool2 !== undefined && this.aduelPlayer === 1) {
            s = '';
        }
        if (this.aduelPlayer === 2 && tool2 === undefined) {
            s += this.getSVGcorrect(this.iconSize, iscorrect2 !== 0, this.colorScore2, this.colorScore2);
        }
        this.strip.innerHTML = s;

        this.strip.style.zIndex = '1';
    }

    showScore(json) {
        super.showScore(this.player1, json.sumscore, json.rank, json.percent, json.percentRank, true);
        this.player1.lblAddScore.innerHTML = json.addscore === undefined ? '' : json.addscore;
        this.autoResizeText(this.player1.lblAddScore, this.iconSize, this.player1.heightLine3, true, 0, 0, 1);

        if (json.aduelPlayer === 2) {
            this.player2.divMain.style.visibility = 'visible';
            super.showScore(this.player2, json.aduelScore, json.aduelRank, json.aduelPercent, json.aduelPercentRank, true);
        } else {
            this.player2.divMain.style.visibility = 'hidden';
            super.showScore(this.player2, '', '', '', '', false);
        }
    }

    sendGetHighScore() {
        let params = {
            mmogameid: this.mmogameid,
            kinduser: this.kinduser,
            user: this.user,
            count: 10,
        };

        require(['core/ajax'], (Ajax) => {
            // Calling the service through the Moodle AJAX API
            let getHighScore = Ajax.call([{
                methodname: 'mmogametype_quiz_get_highscore',
                args: params
            }]);

            // Handling the response
            getHighScore[0].done((response) => {
                this.createScreenHighScore(JSON.parse(response));
            }).fail((error) => {
                return error;
            });
        });
    }

    createScreenHighScore(json) {
        if (this.highScore !== undefined) {
            this.body.removeChild(this.highScore);
            this.highScore = undefined;
            this.strip.style.visibility = 'visible';
            this.strip.style.zIndex = '1';
            return;
        }

        this.removeDivMessage();

        let child;
        while ((child = this.area.firstChild)) {
            this.area.removeChild(child);
        }

        if (this.isVertical) {
            this.createScreenVerticalHighScore(json);
        } else {
            this.createScreenHorizontalHighScore(json);
        }
    }

    createScreenHorizontalHighScore(json) {
        this.createScreenVerticalHighScore(json);
    }

    createScreenVerticalHighScore(json) {
        if (json.count === 0) {
            return;
        }

        if (this.strip !== undefined) {
            this.strip.style.visibility = 'hidden';
        }

        this.highScore = this.createDivColor(this.body, 'mmogame-quiz-aduel-highscore-background',
            this.areaRect.left, this.areaRect.top,
            this.areaRect.width - this.padding, this.areaRect.height - this.padding,
            this.getColorHex(this.colorBackground));

        let canvas = document.createElement('canvas');
        canvas.style.left = '0px';
        canvas.style.top = "0px";
        canvas.width = this.areaRect.width;
        canvas.height = this.areaRect.height;
        canvas.style.zIndex = 8;
        canvas.style.position = "absolute";

        this.highScore.appendChild(canvas);

        let ctx = canvas.getContext("2d");
        let fontSize = this.minFontSize;
        ctx.font = fontSize + "px sans-serif";

        this.drawHighScore1(json, ctx, fontSize);
    }

    drawHighScore1(json, ctx, fontSize) {
        ctx.textAlign = "center";
        let text1 = ctx.measureText(this.getStringT('js_ranking_order'));
        let width1 = text1.width;
        let text = ctx.measureText(this.getStringM('js_grade'));
        let width2 = text.width;

        let col1 = 0;
        let col2 = col1 + width1 + this.padding;
        let col3 = col2 + width2 + this.padding;
        let row = Math.round(3 * fontSize * 1.2);

        let results = JSON.parse(json.results);

        ctx.fillStyle = this.getContrastingColor(this.colorBackground);

        let y = Math.round(fontSize * 1.2);
        ctx.textAlign = "center";
        ctx.fillText(this.getStringT('js_ranking_order'), col1 + width1 / 2, y);

        ctx.fillText(this.getStringM('js_grade'), col2 + width2 / 2, y);

        ctx.textAlign = "left";
        ctx.fillText(this.getStringM('js_name'), col3, y);

        results.forEach(player => {
            y += row / 2 + this.padding;

            ctx.textAlign = "center";
            ctx.fillText(player.rank, col1 + width1 / 2, y);

            ctx.fillText(player.score, col2 + width2 / 2, y);

            ctx.textAlign = "left";
            ctx.fillText(player.name, col3 + row - this.padding, y);

            this.createImage(this.highScore, 'mmogame-quiz-aduel-player-avatars',
                col3, y - row / 2, row - this.padding, row - this.padding,
                'assets/avatars/' + player.avatar);

            y += row / 2 - this.padding;
        });
    }

    showHelpScreen(div) {
        div.innerHTML = `
<div>` + this.getStringT('js_aduel_help') + `</div>

<table class="mmogame-table-help">
    <tr>
        <td class="mmogame-td-help-image">
            <img height="90" src="../../../../assets/cutred.svg" alt="" />
        </td>
        <td>\` + this.getStringT('js_aduel_cut') + \`</td>
        <td class="mmogame-td-help-image">
            <img height="90" src="../../../../assets/skip.svg" alt="" />
        </td>
        <td>\` + this.getStringT('js_aduel_skip') + \`</td>
        <td class="mmogame-td-help-image">
            <img height="90" src="../../../../assets/wizard.svg" alt="" />
        </td>
        <td>\` + this.getStringT('js_aduel_wizard') + \`</td>
    </tr>

    <tr>
        <td class="mmogame-td-help-image">
            <img height="90" src="type/quiz/assets/aduel/example1.png" alt="" />
        </td>

        <td>` + this.getStringT('js_aduel_example1') + `</td>
        <td class="mmogame-td-help-image">
            <img height="83" src="type/quiz/assets/aduel/example2.png" alt="" />
        </td>

        <td>\` + this.getStringT( 'js_aduel_example2') + \`</td>
    </tr>
</table>        
        `;
    }

    /**
     * Creates a timer display.
     * @param {any} parent
     * @param {number} left - Left position in pixels.
     * @param {number} top - Top position in pixels.
     * @param {number} size - Timer size.
     */
    createDivTimer(parent, left, top, size) {
        this.divTimer = this.createDOMElement('div', {
            parent: parent,
            classnames: 'mmogame-quiz-aduel-timer',
            styles: {
                position: 'absolute',
                left: `${left}px`,
                top: `${top}px`,
                width: `${size}px`,
                height: `${size}px`,
                textAlign: 'center',
                color: this.getContrastingColor(this.colorBackground),
            },
            attributes: {
                alt: this.getStringM('js_question_time'),
            },
        });

        this.divTimer.innerHTML = '23:59';
        this.autoResizeText(this.divTimer, size, size, false, 0, 0);
        this.divTimer.innerHTML = '';
        this.divTimer.title = this.getStringM('js_question_time');
    }

    updateDivTimer() {
        // Exit if labelTimer or timeclose are undefined
        if (!this.divTimer || !this.timeclose) {
            return;
        }

        // Calculate the remaining time in seconds
        const now = Date.now() / 1000; // Get current time in seconds
        let remainingTime = Math.max(0, this.timeclose - now);

        // If no time is remaining, clear the label and handle timeout
        if (remainingTime === 0) {
            this.divTimer.innerHTML = '';
            this.onTimeout();
            return;
        }

        // Format the remaining time as mm:ss
        const minutes = Math.floor(remainingTime / 60);
        const seconds = String(Math.floor(remainingTime % 60)).padStart(2, '0');
        this.divTimer.innerHTML = `${minutes}:${seconds}`;

        // Set a timeout to update the timer every 500ms
        this.timerTimeout = setTimeout(() => this.updateDivTimer(), 500);
    }
    createScreen(json, disabled) {
        if (this.endofgame) {
            // Display end-of-game message and final score
            this.createDivMessage('mmogame-endofgame', this.getStringM('js_game_over'));
            this.showScore(json);
            return;
        }

        this.removeAreaChildren();

        // Render the screen layout based on orientation (vertical or horizontal)
        if (this.isVertical) {
            this.createScreenVertical(json, disabled);
        } else {
            this.createScreenHorizontal(json, disabled);
        }

        // Display the current score
        this.showScore(json);
    }

    createScreenHorizontal(json, disabled) {
        const [width] = this.computeBestFontSize(json);

        this.radioSize = Math.round(this.fontSize);
        this.stripTop = this.createAnswer(width, 0, width - this.padding, false, this.fontSize, disabled);

        this.createDefinition(0, 0, width - this.padding, this.stripTop - this.padding,
            false, this.fontSize, json.definition);

        // Adjust strip dimensions
        this.stripLeft = width + this.padding;
    }

    createScreenVertical(json, disabled) {
        this.computeBestFontSize(json);

        this.radioSize = Math.round(this.fontSize);

        const defSize = this.createDefinition(0, 0, this.areaRect.width, 0, false, this.fontSize, json.definition);
        this.stripTop = this.createAnswer(0, defSize[1] + this.padding, this.areaRect.width, false, this.fontSize, disabled);

        // Adjust strip position
        this.stripLeft = 0;
    }

    showWaitOpponent() {
        if (this.isWaitOpponent) {
            return;
        }
        this.updateNicknameAvatar(this.player2, '', '', 0, 0);
        this.createDivMessage(this.getStringT('js_aduel_wait_opponent'));
        if (this.divTimer !== undefined) {
            this.divTimer.innerHTML = "";
        }
    }

    };
});