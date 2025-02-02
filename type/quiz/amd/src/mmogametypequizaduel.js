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
    isWaitOpponent; // True if wait someone to answer a set of questions
    nickNameHeight; // Height of nickname

    constructor() {
        super();

        // Initialize core properties
        this.cIcons = this.isVertical ? 5 : 8 + (this.hasHelp() ? 1 : 0);

        this.isWaitOpponent = false;
    }

    createIconBar() {
        let i = 0;
        const step = this.iconSize + this.padding;

        const nicknameHeight = Math.round(this.iconSize / 3);

        let nickname, avatar;
        [nickname, avatar] = this.createNicknameAvatar('mmogame-quiz-alone',
            Math.round(this.padding + (i++) * step),
            this.padding,
            2 * this.iconSize + this.padding,
            nicknameHeight,
            this.padding + nicknameHeight,
            this.iconSize);

        this.player1 = this.createDivScorePercent('mmogame-quiz-aduel-player1',
            this.padding + (i++) * (this.iconSize + this.padding), this.padding + this.nickNameHeight,
            this.getContrastingColor(this.color), true);
        this.player1.avatarElement = avatar;
        this.player1.nicknameElement = nickname;
        this.player1.cacheAvatar = '';
        this.player1.cacheNickname = '';

        [nickname, avatar] = this.createNicknameAvatar('mmogame-quiz-alone',
            Math.round(this.padding + (i++) * step),
            this.padding,
            2 * this.iconSize + this.padding,
            nicknameHeight,
            this.padding + nicknameHeight,
            this.iconSize);

        this.player2 = this.createDivScorePercent('mmogame-quiz-aduel-player2',
            this.padding + (i++) * (this.iconSize + this.padding), this.padding + this.nickNameHeight,
            this.getContrastingColor(this.color), false);
        this.player2.avatarElement = avatar;
        this.player2.nicknameElement = nickname;
        this.player2.cacheAvatar = '';
        this.player2.cacheNickname = '';

        this.createButtonSound(this.padding + (i++) * step,
            this.padding + nicknameHeight, this.iconSize);
        if (this.hasHelp()) {
            this.createButtonHelp(this.padding + (i++) * step, this.padding);
            this.buttonHelp.addEventListener("click", () => this.onClickHelp(this.buttonHelp));
        }

        this.areaRect = {
            left: this.padding,
            top: 2 * this.padding + this.iconSize + nicknameHeight,
            width: Math.round(window.innerWidth - 2 * this.padding),
            height: Math.round(window.innerHeight - 2 * this.padding - nicknameHeight - this.iconSize),
        };

        if (this.isVertical) {
            this.areaRect.height -= this.iconSize + 2 * this.padding;
        }
/* B
        this.buttonAvatarHeight = this.iconSize + this.nickNameHeight;
        this.createButtonsAvatar(1, Math.round(this.padding + (i++) * (this.iconSize + this.padding)),
            2 * this.iconSize + this.padding, this.nickNameHeight);
        this.buttonsAvatar[1].style.top = (this.padding + this.nickNameHeight) + "px";
*/

/* A
        this.createButtonsAvatar(2, Math.round(this.padding + (i++) * (this.iconSize + this.padding)),
            2 * this.iconSize + this.padding, this.nickNameHeight);
        this.buttonsAvatar[2].style.top = (this.padding + this.nickNameHeight) + "px";
*/
        this.createDivTimer(this.padding + (i++) * (this.iconSize + this.padding), this.padding + this.nickNameHeight,
            this.iconSize);
        if (this.isVertical) {
            this.createButtonSound(this.padding + (i++) * (this.iconSize + this.padding), this.padding + nicknameHeight);
        } else {
            this.createButtonSound(this.padding, this.areaRect.top + this.areaRect.height);
        }

        if (this.hasBasBottom === false) {
            this.buttonHighScore = this.createImageButton(this.body, 'mmogame-quiz-highscore',
                this.padding + i * (this.iconSize + this.padding),
                this.padding + this.nickNameHeight, this.iconSize, this.iconSize, 'assets/highscore.svg');

            this.button5050 = this.createImageButton(this.body, 'mmogame-quiz-aduel-5050',
                this.padding + (i++) * (this.iconSize + this.padding),
                this.padding + this.nickNameHeight, this.iconSize, this.iconSize, 'assets/cutred.svg');
        } else {
            this.buttonHighScore = this.createImageButton(this.body, 'mmogame-quiz-highscore',
                this.padding + (this.iconSize + this.padding), this.areaTop + this.areaHeight,
                    this.iconSize, this.iconSize, 'assets/highscore.svg');

            this.button5050 = this.createImageButton(this.body, 'mmogame-quiz-aduel-5050',
                this.padding + (this.iconSize + this.padding), this.areaTop + this.areaHeight,
                this.iconSize, this.iconSize, 'assets/cutred.svg');
        }
        this.buttonHighScore.style.visibility = 'hidden';
        this.buttonHighScore.addEventListener("click", () => {
            this.sendGetHighScore();
        });
        this.button5050.addEventListener("click", () => {
            this.sendGetAttempt(false, "tool1");
        });
        this.button5050.title = this.getStringM('js_help_5050');

        let left;
        if (this.isVertical) {
            left = this.padding + (i++) * (this.iconSize + this.padding);
            this.buttonSkip = this.createImageButton(this.body, 'mmogame-quiz-aduel-skip',
                left, this.padding + this.nickNameHeight, this.iconSize,
                this.iconSize, 'assets/skip.svg');
        } else {
            this.buttonSkip = this.createImageButton(this.body, 'mmogame-quiz-aduel-skip',
                this.padding + 2 * (this.iconSize + this.padding),
                this.areaTop + this.areaHeight, this.iconSize, this.iconSize, 'assets/skip.svg');
        }
        this.buttonSkip.addEventListener("click", () => {
            this.sendAnswer("tool2");
        });
        this.buttonSkip.title = this.getStringT('js_help_skip');

        if (!this.isVertical) {
            this.buttonWizard = this.createImageButton(this.body, 'mmogame-quiz-aduel-wizard',
                left, this.padding + this.nickNameHeight, this.iconSize,
                this.iconSize, 'assets/wizard.svg');
        } else {
            this.buttonWizard = this.createImageButton(this.body, 'mmogame-quiz-aduel-wizard',
                this.padding + 2 * (this.iconSize + this.padding),
                this.areaRect.top + this.areaRect.height, this.iconSize, this.iconSize, 'assets/wizard.svg');
        }
        this.buttonWizard.addEventListener("click", () => {
            this.sendGetAttempt(false, "tool3");
        });
        this.buttonWizard.style.visibility = 'hidden';
        this.buttonWizard.title = this.getStringT('js_wizarg');

        if (this.hasHelp()) {
            if (!this.isVertical) {
                this.createButtonHelp(this.padding + (i++) * (this.iconSize + this.padding), this.padding + this.nickNameHeight);
            } else {
                this.createButtonHelp(this.padding + 3 * (this.iconSize + this.padding), this.areaRect.top + this.areaRect.height);
            }
            this.buttonWizard.addEventListener("click", () => {
                this.sendGetAttempt(false, "tool3");
            });
        }
    }

    updateButtonTool(btn, tool) {
        btn.style.visibility = tool !== undefined && tool !== 0 ? "hidden" : "visible";
    }

    processGetAttempt(json) {
        if (this.buttonHighScore !== undefined) {
            this.buttonHighScore.style.visibility = 'hidden';
        }
        this.computeDifClock(json.time, json.timestart, json.timeclose);
        this.timefastjson = json.timefastjson;
        this.fastjson = json.fastjson;

        if (json.name !== undefined) {
            window.document.title = json.name;
        }

        this.correct = undefined;
        if (json.state === 0) {
            json.qtype = '';
            super.processGetAttempt(json);
            this.showScore(json);
            this.onServerGetAttemptHideButtons();
            this.createDivMessageStart(this.getStringM('js_wait_to_start'));
            return;
        }

        this.state = parseInt(json.state);

        // Need for a change on colors.
        if (this.savedColors === undefined || this.savedColors !== json.colors) {
            this.savedColors = json.colors;
            this.colors = undefined;
        }

        if (this.colors === undefined) {
            this.setColorsString(json.colors);
            this.createIconBar();
        }

        const nicknameWidth = 2 * this.iconSize + this.padding;
        const nicknameHeight = this.iconSize / 3;
        this.updateAvatarNickname(this.player1, json.avatar, json.nickname, nicknameWidth, nicknameHeight);
        this.updateAvatarNickname(this.player2, json.aduelAvatar, json.aduelNickname, nicknameWidth, nicknameHeight);

        // This.updateButtonsAvatar(1, json.avatar, json.nickname);
        // This.updateButtonsAvatar(2, json.aduelAvatar, json.aduelNickname);

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
                this.waitOponent();
            }
            this.hideTools();
            this.showScore(json);
            this.onServerGetAttemptRetry();
            return;
        }

        this.isWaitOponent = false;

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
        this.autosave = json.autosave !== 0;
        this.errorcode = json.errorcode;

        // A this.readJsonFiles(json);
        this.createScreen(json, false);
        if (this.btnSubmit !== undefined) {
            this.btnSubmit.style.visibility = "hidden";
        }

        this.updateLabelTimer();
        this.sendFastJSON();
    }

    onServerGetAttemptHideButtons() {
        this.button5050.style.visibility = 'hidden';
        this.buttonSkip.style.visibility = 'hidden';
        if (this.btnSubmit !== undefined) {
            this.btnSubmit.style.visibility = "hidden";
        }
        this.buttonsAvatar[2].style.visibility = 'hidden';
    }

    onTimeout() {
        this.labelTimer.innerHTML = '';
        this.disableInput();
        if (this.btnSubmit !== undefined) {
            this.btnSubmit.style.display = 'none';
        }

        this.answer = '';
        this.answerid = 0;
        this.callSetAnswer();

        let btn = super.createImageButton(this.area, 'mmogame-quiz-next',
            this.nextLeft, this.nextTop, 0, this.iconSize, 'assets/next.svg');
        btn.title = this.getStringM('js_next_question');
        btn.addEventListener("click", () => {
            this.sendGetAttempt(false);
            this.area.removeChild(btn);
        });
    }

    waitOpponent() {
        if (this.isWaitOponent) {
            return;
        }
        this.updateAvatarNickname(this.player2, "", "");
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
        if (json.submit === 0) {
            return;
        }

        this.correct = json.correct;

        if (json.correct !== undefined) {
            if (this.qtype === "multichoice") {
                this.onServerAnswerMultichoice(json);
            }
        }

        this.disableInput();
        this.buttonHighScore.style.visibility = 'visible';

        this.isWaitOponent = false;
        if (this.aItemAnswer !== undefined && json.correct !== undefined) {
            for (let i = 0; i < this.aItemAnswer.length; i++) {
                this.aItemAnswer[i].classList.add("disabled");
            }
        }

        if (this.btnSubmit !== undefined) {
            this.body.removeChild(this.btnSubmit);
            this.btnSubmit = undefined;
        }

        this.showCorrectAnswer(json);

        let btn = super.createImageButton(this.area, 'mmogame-quiz-next',
            this.nextLeft, this.nextTop, 0, this.iconSize, 'assets/next.svg');
        btn.title = this.getStringM('js_next_question');
        btn.addEventListener("click", () => {
            this.callGetAttempt();
            this.area.removeChild(btn);
        });

        this.showScore(json);

        this.timeclose = 0;

        if (this.aduelPlayer === 1) {
            if (json.attempt === 0) {
                this.timeclose = 0;
                this.waitOpponent();
            }

            this.showScore(json);
        }

        if (this.button5050 !== undefined) {
            this.button5050.style.visibility = 'hidden';
            this.buttonSkip.style.visibility = 'hidden';
        }
    }

   callSetAnswer() {
        if (this.correct === undefined) {
            super.callSetAnswer();
        }
    }

    onServerAnswerMultichoice(json) {
        let foundCorrect = false;

        let aduelAnswers = json.aduelPlayer === 2 && json.aduel_useranswer !== null ? json.aduel_useranswer.split(",") : '';
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

    showCorrectAnswer({aduel_iscorrect, iscorrect, tool2}) {
        this.timeclose = 0;
        this.updateLabelTimer();

        this.strip = this.createDiv(this.area, 'mmogame-quiz-aduel-strip',
            this.stripLeft, this.nextTop, this.stripWidth, this.stripHeight);

        if (tool2 === undefined || this.aduelPlayer === 2) {
            this.createImage(this.area, 'mmogame-quiz-aduel-player1',
                this.stripLeft, this.nextTop, this.buttonsAvatar[1].width,
            this.buttonsAvatar[1].height, this.buttonsAvatar[1].src);
        }

        if (tool2 === undefined && this.aduelPlayer === 2) {
            this.createImage(this.area, 'mmogame-quiz-aduel-player2',
                this.stripLeft + this.iconSize, this.nextTop, this.buttonsAvatar[2].width,
                this.buttonsAvatar[2].height, this.buttonsAvatar[2].src);
        }

        this.strip.style.top = this.nextTop + "px";
        let s = this.getSVGcorrect(this.iconSize, iscorrect !== 0, this.colorScore, this.colorScore);
        if (tool2 !== undefined && this.aduelPlayer === 1) {
            s = '';
        }
        if (this.aduelPlayer === 2 && tool2 === undefined) {
            s += this.getSVGcorrect(this.iconSize, aduel_iscorrect !== 0, this.colorScore2, this.colorScore2);
        }
        this.strip.innerHTML = s;

        this.strip.style.zIndex = '1';
    }

    showScore(json) {
        let rankc = json.completedrank;
        if (json.rank !== undefined && rankc !== undefined) {
            if (parseInt(json.rank) < parseInt(rankc)) {
                json.completedrank = '';
                json.rank = '# ' + json.rank;
            } else {
                json.rank = '';
                json.completedrank = '# ' + rankc;
            }
        }

        if (json.aduelPlayer === 2) {
            super.showScore(json);
        } else {
            let s = json.sumscore;
            let sumscore = this.labelScore.innerHTML;
            super.showScore(json);
            json.sumscore = s;
            s = sumscore === undefined ? '' : '<b>' + sumscore + '</b>';
            if (this.labelScore.innerHTML !== s) {
                this.labelScore.innerHTML = s;
                this.autoResizeText(this.labelScore, 0.8 * this.iconSize / 2, this.iconSize / 2, false, 0, 0);
            }
        }

        if (json.aduelPlayer === 1 || json.aduelPlayer === undefined) {
            this.labelScore2.style.visibility = "hidden";
            this.labelScoreRank2.style.visibility = "hidden";
            this.buttonScore2.style.visibility = "hidden";
            this.labelAddScore2.style.visibility = "hidden";
        } else {
            this.labelScore2.style.visibility = "visible";
            this.labelScoreRank2.style.visibility = "visible";
            this.buttonScore2.style.visibility = "visible";
            this.labelAddScore2.style.visibility = "visible";

            let rank = json.aduelRank;
            let score = json.aduelScore;

            if (json.aduelRank !== undefined && json.aduelCompletedrank !== undefined) {
                let rank1 = parseInt(json.aduelRank);
                let rank2 = parseInt(json.aduelCompletedrank);
                if (rank1 <= rank2) {
                    rank = '#' + rank1;
                    score = json.aduelScore;
                    this.labelScore2.title = this.getStringM('js_grade');
                    this.labelScoreRank2.title = this.getStringM('js_position_grade');
                } else {
                    rank = '#' + rank2;
                    score = Math.round(100 * json.aduelCompletedrank) + "%";
                    this.labelScore2.title = this.getStringM('js_percent_opponent');
                    this.labelScoreRank2.title = this.getStringM('js_position_percent');
                }
            }
            let s = '<b>' + score + '</b>';
            if (this.labelScore2.innerHTML !== s) {
                this.labelScore2.innerHTML = s;
                this.autoResizeText(this.labelScore2, this.iconSize - 2 * this.padding, this.iconSize / 2, false, 0, 0, 1);
            }
            this.labelScoreRank2.innerHTML = rank;
            this.labelScoreRank2.style.lineHeight = (this.iconSize / 3) + "px";
            this.autoResizeText(this.labelScoreRank2, 0.5 * this.iconSize, this.iconSize / 3, true, 0, 0, 1);

            this.labelAddScore2.innerHTML = json.aduelAddscore === undefined ? '' : json.aduelAddscore;
            this.autoResizeText(this.labelAddScore2, this.iconSize, this.iconSize / 3, true, 0, 0, 1);
        }
    }

    onServerFastJson(response) {
        if (response === '') {
            this.sendFastJSON();
            return;
        }

        let a = response.split('-'); // Are state,timefastjson.
        let newstate = a.length > 0 ? parseInt(a[0]) : 0;
        let newTimeFastJSON = a.length > 1 ? a[1] : 0;

        if (this.timefastjson === null) {
            this.timefastjson = 0;
        }

        if (newstate !== this.state || newTimeFastJSON !== this.timefastjson) {
            this.callGetAttempt();
            return;
        }

        this.sendFastJSON();
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

        if (this.vertical) {
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
            this.areaLeft, this.areaTop,
            this.areaWidth - this.padding, this.areaHeight - this.padding,
            this.getColorHex(this.colorBackground));

        let canvas = document.createElement('canvas');
        canvas.style.left = '0px';
        canvas.style.top = "0px";
        canvas.width = this.areaWidth;
        canvas.height = this.areaHeight;
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

<table border=1>
    <tr>
        <td><center>
            <img height="90" src="../../../../assets/cutred.svg" alt="" />
        </td>
        <td>\` + this.getStringT('js_aduel_cut') + \`</td>
        <td><center>
            <img height="90" src="../../../../assets/skip.svg" alt="" />
        </td>
        <td>\` + this.getStringT('js_aduel_skip') + \`</td>
        <td><center>
            <img height="90" src="../../../../assets/wizard.svg" alt="" />
        </td>
        <td>\` + this.getStringT('js_aduel_wizard') + \`</td>
    </tr>

    <tr>
        <td><center>
            <img height="90" src="../../assets/aduel/example1.png" alt="" />
        </td><center>

        <td>` + this.getStringT('js_aduel_example1') + `</td>
        <td><center>
            <img height="83" src="../../assets/aduel/example2.png" alt="" />
        </td>

        <td>\` + this.getStringT( 'js_aduel_example2') + \`</td>
    </tr>
</table>        
        `;
    }

    /**
     * Creates a timer display.
     * @param {number} left - Left position in pixels.
     * @param {number} top - Top position in pixels.
     * @param {number} size - Timer size.
     */
    createDivTimer(left, top, size) {
        const timerDiv = this.createDiv(this.body, 'mmogame-timer', left, top, size, size);
        timerDiv.style.textAlign = 'center';
        timerDiv.style.color = this.getContrastingColor(this.colorBackground);

        timerDiv.innerHTML = '23:59';
        this.autoResizeText(timerDiv, size, size, false, 0, 0, 1);
        timerDiv.innerHTML = '';
        timerDiv.title = this.getStringM('js_question_time');

        this.labelTimer = timerDiv;
    }



        };
});