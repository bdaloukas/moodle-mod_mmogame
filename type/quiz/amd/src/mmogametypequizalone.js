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
            super();
            this.cIcons = this.hasHelp() ? 5 : 4;
            this.type = 'alone';
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
            this.createArea(2 * this.padding + this.iconSize + nicknameHeight);

            this.body.appendChild(fragment); // Batch insert into DOM
        }

        processSetAnswer(json) {
            this.correct = json.correct;

            this.playAudio(json.iscorrect !== 0 ? this.audioYes : this.audioNo);

            if (json.correct !== undefined && this.qtype === "multichoice") {
                this.updateScreenAfterAnswerMultichoice();
            }

            this.disableInput();

            const btn = super.createImageButton(
                this.area,
                'mmogame-quiz-next',
                this.nextLeft,
                this.nextTop,
                0,
                this.iconSize,
                'assets/next.svg'
            );
            btn.addEventListener("click", () => {
                this.callGetAttempt();
                this.area.removeChild(btn);
            });

            this.showScore(json);
        }

        onServerFastJson(response) {
            const [state, timefastjson] = response.split("-").map(Number);
            if (timefastjson !== this.timefastjson || state !== this.state) {
                this.callGetAttempt();
                return;
            }

            this.sendFastJSON();
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
                this.createDivMessage('mmogame-endofgame', this.getStringM('js_game_over'));
                this.showScore(json);
                return;
            }

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
            // Reserve space for next button
            let maxHeight = this.areaRect.height - 2 * this.padding - this.iconSize;

            const width = Math.round((this.areaRect.width - this.padding) / 2 - this.padding);
            for (let step = 1; step <= 2; step++) {
                let defSize;
                this.fontSize = this.findbest(step === 1 ? this.minFontSize : this.minFontSize / 2, this.maxFontSize,
                    (fontSize) => {
                        defSize = this.createDefinition(0, 0, width - this.padding, 0, true, fontSize,
                            json.definition);

                        if (defSize[0] >= width) {
                            return 1;
                        }
                        let ansSize = this.createAnswer(0, 0, width - this.padding, true, fontSize, disabled);
                        if (ansSize[0] >= width) {
                            return 1;
                        }
                        return defSize[1] < maxHeight && ansSize[1] < maxHeight ? -1 : 1;
                    }
                );
                if (defSize[0] <= width && defSize[1] <= maxHeight) {
                    break;
                }
            }

            this.radioSize = Math.round(this.fontSize);
            const heightAnswer = this.createAnswer(width, 0, width - this.padding, false, this.fontSize, disabled);

            this.createDefinition(0, 0, width - this.padding, heightAnswer, false, this.fontSize, json.definition);

            this.nextTop = heightAnswer + this.padding;

            // Adjust strip dimensions
            this.stripLeft = width + this.padding;
            this.stripWidth = 2 * this.iconSize;
            this.stripHeight = this.iconSize;
        }

        createScreenVertical(json, disabled) {
            let maxHeight = this.areaRect.height - 2 * this.padding - this.iconSize;

            for (let step = 1; step <= 2; step++) {
                let defSize, ansSize;
                this.fontSize = this.findbest(step === 1 ? this.minFontSize : this.minFontSize / 2, this.maxFontSize,
                    (fontSize) => {
                        defSize = this.createDefinition(0, 0, this.areaRect.width, 0, true, fontSize,
                            json.definition);
                        ansSize = this.createAnswer(0, 0, this.areaRect.width, true, fontSize, disabled);

                        if (defSize[0] >= this.areaRect.width || ansSize[0] >= this.areaRect.width) {
                            return 1;
                        }
                        return defSize[1] + ansSize[1] < maxHeight ? -1 : 1;
                    }
                );
                if (defSize[0] <= this.areaRect.width && defSize[1] + ansSize[1] <= maxHeight) {
                    break;
                }
            }

            this.radioSize = Math.round(this.fontSize);
            const defSize = this.createDefinition(0, 0, this.areaRect.width, 0, false, this.fontSize, json.definition);

            this.nextTop = this.createAnswer(
                0, defSize[1] + this.padding, this.areaRect.width, false, this.fontSize, disabled) + this.padding;

            // Adjust strip dimensions
            this.stripLeft = this.iconSize + this.padding;
            this.stripWidth = 2 * this.iconSize;
            this.stripHeight = this.iconSize;
        }

        processGetAttempt(json) {
            // Calculate time difference and set up the clock
            this.computeDifClock(json.time, json.timestart, json.timeclose);

            if (parseInt(json.state) === 0) {
                json.qtype = '';
                if (json.colors) {
                    this.setColorsString(json.colors);
                    this.createIconBar();
                }
                this.showScore(json);
                this.createDivMessageStart(this.getStringM('js_wait_to_start'));
                return;
            }

            if (this.player === undefined) {
                this.setColorsString(json.colors);
                this.createIconBar();
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

            // Update game state and user-related data
            this.state = parseInt(json.state, 10);
            this.fastjson = json.fastjson;
            this.timefastjson = parseInt(json.timefastjson, 10);

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

            // A this.updateLabelTimer(); // Start or update the timer
            this.sendFastJSON(); // Send fast JSON updates
        }

        showScore({sumscore, rank, percent, percentrank}) {
            super.showScore(this.player, sumscore, rank, percent, percentrank, true);
        }

        showHelpScreen(div) {
            div.innerHTML = `
                <br>
                <div>${this.getStringT('js_alone_help')}</div><br>

                <table class="mmogame-table-help">
                    <tr>
                        <td><img height="83" src="assets/aduel/example2.png" alt="" /></td>
                        <td>${this.getStringT('js_aduel_example2')}</td>
                    </tr>
                </table>
            `;
        }
    };
});