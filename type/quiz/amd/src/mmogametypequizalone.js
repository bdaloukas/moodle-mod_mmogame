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
    return class MmoGameTypeQuizAlone extends MmoGameTypeQuiz {

        // Score variables.
        player;

        constructor() {
            super('alone');
            this.cIcons = this.hasHelp() ? 5 : 4;
        }

        createIconBar() {
            let i = 0;
            const step = this.iconSize + this.padding;

            const nicknameHeight = Math.round(this.iconSize / 3);

            const fragment = document.createDocumentFragment(); // Batch DOM updates

            const [nickname, avatar] = this.createNicknameAvatar(fragment,
                'mmogame-quiz-alone',
                Math.round(this.padding + (i++) * step),
                this.padding,
                2 * this.iconSize + this.padding,
                nicknameHeight,
                this.padding + nicknameHeight,
                this.iconSize);

            this.player = this.createDivScorePercent(fragment,
                'mmogame-quiz-alone',
                this.padding + (i++) * step,
                this.padding + nicknameHeight,
                true,
                this.colorScore);
            this.player.avatarElement = avatar;
            this.player.nicknameElement = nickname;
            this.player.cacheAvatar = '';
            this.player.cacheNickname = '';
            this.player.lblScore.title = this.getStringM('js_grade');
            this.player.lblRank.title = this.getStringM('js_ranking_grade');

            this.createButtonSound(fragment, this.padding + (i++) * step,
                this.padding + nicknameHeight, this.iconSize);
            if (this.hasHelp()) {
                const button = this.createButtonHelp(fragment, this.padding + (i++) * step, this.padding);
                button.addEventListener("click", () => this.onClickHelp());
            }

            this.areaRect = {top: 2 * this.padding + this.iconSize + nicknameHeight};

            this.body.appendChild(fragment); // Batch insert into DOM
        }

        processSetAnswer(json) {
            this.correct = json.correct;

            if (json.correct !== undefined) {
                if (this.qtype === "multichoice") {
                    this.onServerAnswerMultichoice(json);
                }
            }

            this.disableInput();

            if (this.aItemAnswer !== undefined && json.correct !== undefined) {
                for (let i = 0; i < this.aItemAnswer.length; i++) {
                    this.aItemAnswer[i].classList.add("disabled");
                }
            }

            this.createNextButton(this.areaRect.width - this.iconSize - this.padding, this.stripTop);

            this.showScore(json);
        }

        /**
         * Creates the game screen layout based on the current state.
         *
         * @param {Object} json - The game data used to build the screen.
         * @param {boolean} disabled - Determines whether user input should be disabled.
         */
        createScreen(json, disabled) {
            if (this.endofgame) {
                // Display end-of-game message and final score
                this.createDivMessage('mmogame-quiz-alone-endofgame', this.getStringM('js_game_over'));
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

        /**
         * Creates a horizontal layout for the quiz screen.
         *
         * @param {object} json
         * @param {boolean} disabled - Whether user input should be disabled.
         */
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

        processGetAttempt(json) {
            super.processGetAttempt(json);
            if (json.name !== undefined) {
                window.document.title = json.name;
            }

            // Update game state
            this.state = parseInt(json.state, 10);
            if (this.state === 0) {
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

            if (this.player === undefined) {
                this.setColorsString(json.colors);
                this.createIconBar();
            }

            if (this.area === undefined) {
                this.createArea(this.areaRect.top, 0);
            }

            // Update the window title if a name is provided
            if (json.name) {
                document.title = json.name;
            }

            // Handle error messages from the server
            if (json.errorcode) {
                this.createDivMessage('mmogame-error', json.errorcode);
                return;
            }

            const nicknameWidth = 2 * this.iconSize + this.padding;
            const nicknameHeight = this.iconSize / 3;
            this.updateNicknameAvatar(this.player, json.avatar, json.nickname, nicknameWidth, nicknameHeight);
            this.attempt = json.attempt;

            // Process question type and answers
            this.qtype = json.qtype;
            if (this.qtype === 'multichoice') {
                this.answers = [];
                this.answersID = json.answerids;
                json.answers.forEach((answer, index) => {
                    this.answers[index] = this.repairP(answer); // Process each answer
                });
            }
            this.answer = json.answer ?? undefined;

            // Handle end-of-game scenarios
            this.endofgame = json.endofgame !== undefined && json.endofgame !== 0;
            json.definition = this.repairP(json.definition);
            this.errorcode = json.errorcode;

            if (json.state !== 0) {
                this.createScreen(json, false);
            }

            this.sendFastJSON(); // Send fast JSON updates
        }

        showScore(json) {
            super.showScore(this.player, json.sumscore, json.rank, json.percent, json.percentRank, true);
            this.player.lblAddScore.innerHTML = json.addscore === undefined ? '' : json.addscore;
            this.autoResizeText(this.player.lblAddScore, this.player.cellSize, this.player.cellSize, true, 0, 0, 1);
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

        onServerAnswerMultichoice(json) {
            let foundCorrect = false;

            let aCorrect = json.correct.split(",");
            for (let i = 0; i < this.answersID.length; i++) {
                if (this.answersID[i] === '') {
                    continue;
                }

                const label = this.aItemLabel[i];

                let iscorrect1;

                const iscorrect = aCorrect.includes(this.answersID[i]);

                if (this.aItemAnswer[i].classList.contains("checked")) {
                    iscorrect1 = aCorrect.includes(this.answersID[i]);
                }
                if (iscorrect1) {
                    foundCorrect = true;
                }

                if (iscorrect === false && iscorrect1 === undefined) {
                    continue;
                }

                const height = this.aItemLabel[i].scrollHeight;

                const move = iscorrect1 !== undefined ? this.radioSize : 0;
                const width = this.labelWidth - move;

                if (move !== 0) {
                    label.style.left = (parseInt(label.style.left) + move) + "px";
                }
                this.aItemLabel[i].style.width = width + "px";
                this.autoResizeText(this.aItemLabel[i], width, height, true, this.minFontSize, this.maxFontSize, 0.5);

                this.onServerAnswerMultichoiceShowCorrect(i, iscorrect1);
            }

            this.playAudio(foundCorrect ? this.audioYes : this.audioNo);
        }

        onServerAnswerMultichoiceShowCorrect(i, iscorrect1, iscorrect) {
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
        }

    };
});