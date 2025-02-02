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

        cacheScore;

        avatarElement;
        nicknameElement;

        // Score variables.
        player;
        lblScore;
        lblRank;
        lblPercent;
        lblAddScore;

        constructor() {
            super();
            this.cIcons = this.hasHelp() ? 5 : 4;
            this.type = 'alone';
        }

        createIconBar() {
            let i = 0;
            const step = this.iconSize + this.padding;

            const nicknameHeight = Math.round(this.iconSize / 3);

            [this.nicknameElement, this.avatarElement] = this.createNicknameAvatar('mmogame-quiz-alone',
                Math.round(this.padding + (i++) * step),
                this.padding,
                2 * this.iconSize + this.padding,
                nicknameHeight,
                this.padding + nicknameHeight,
                this.iconSize);

            [this.lblScore, this.lblRank, this.lblPercent, this.lblAddScore] = this.createDivScorePercent(
                'mmogame-quiz-alone',
                this.padding + (i++) * step,
                this.padding + nicknameHeight,
                this.getContrastingColor(this.color),
                true);

            this.createButtonSound(this.padding + (i++) * step,
                this.padding + this.nickNameHeight);
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
        }

        processSetAnswer(json) {
            this.correct = json.correct;

            this.playAudio(json.iscorrect !== 0 ? this.audioYes : this.audioNo);

            if (json.correct !== undefined && this.qtype === "multichoice") {
                this.updateScreenAfterAnswerMultichoice();
            }

            this.disableInput();

            if (this.btnSubmit) {
                this.body.removeChild(this.btnSubmit);
                this.btnSubmit = undefined;
            }

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
                this.sendGetAttempt({mmogameid: this.mmogameid, kinduser: this.kinduser, user: this.user});
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
            this.createArea(); // Prepare the main game area

            if (this.endofgame) {
                // Display end-of-game message and final score
                this.createDivMessage('mmogame-endofgame', this.getStringM('js_game_over'));
                this.showScore(json);
                return;
            }

            // Render the screen layout based on orientation (vertical or horizontal)
            if (this.vertical) {
                this.createScreenVertical(disabled);
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
            let maxHeight = this.areaRect.height - 2 * this.padding;

            if (!this.hideSubmit) {
                maxHeight -= this.iconSize + this.padding; // Reserve space for submit button
            }

            const width = Math.round((this.areaRect.width - this.padding) / 2);
            for (let step = 1; step <= 2; step++) {
                let defSize;
                this.fontSize = this.findbest(step === 1 ? this.minFontSize : this.minFontSize / 2, this.maxFontSize,
                    (fontSize) => {
                        defSize = this.createDefinition(0, 0, width - this.padding, true, fontSize,
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
                if (defSize[0] <= width && defSize[1] <= this.areaRect.height) {
                    break;
                }
            }

            this.radioSize = Math.round(this.fontSize);
            this.createDefinition(0, 0, width - this.padding, false, this.fontSize, json.definition);

            this.nextTop = this.createAnswer(width, 0, width - this.padding, false, this.fontSize, disabled) + this.padding;

            if (!this.hideSubmit) {
                // Create and position the submit button
                this.btnSubmit = this.createImageButton(
                    this.body,
                    'mmogame-quiz-submit',
                    width + (width - this.iconSize) / 2,
                    this.nextTop,
                    0,
                    this.iconSize,
                    'assets/submit.svg',
                    false,
                    'submit'
                );
                this.btnSubmit.addEventListener('click', () => {
                    this.sendAnswer();
                });
            }

            // Adjust strip dimensions
            this.stripLeft = width + this.padding;
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

            // Set colors if provided
            if (json.colors) {
                this.setColorsString(json.colors);
                this.createIconBar(); // Initialize the top bar with icons
            }

            // Update the window title if a name is provided
            if (json.name) {
                document.title = json.name;
            }

            // Set help URL if available
            if (json.helpurl) {
                this.helpUrl = json.helpurl;
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
            this.updateButtonsAvatar(this.player, json.avatar, json.nickname);

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

            if (this.btnSubmit) {
                this.btnSubmit.style.visibility = "hidden";
            }
        }

        showScore({rank, rankc, sumscore}) {
            if (rank !== undefined && rankc !== undefined) {
                if (parseInt(rank) < parseInt(rankc)) {
                    this.completedrank = '';
                    this.rank = `# ${rank}`;
                } else {
                    this.rank = '';
                    this.completedrank = `# ${rankc}`;
                }
            }

            const s = sumscore === undefined ? '' : `<b>${sumscore}</b>`;
            if (this.cacheScore !== s) {
                this.cacheScore = s;
                this.lblScore.innerHTML = s;
                this.autoResizeText(this.lblScore, 0.8 * this.iconSize / 2, this.iconSize / 2, false, 0, 0);
            }
        }

        showHelpScreen(div) {
            div.innerHTML = `
                <br>
                <div>${this.getStringT('js_alone_help')}</div><br>

                <table border=1>
                    <tr>
                        <td><img height="83" src="assets/aduel/example2.png" alt="" /></td>
                        <td>${this.getStringT('js_aduel_example2')}</td>
                    </tr>
                </table>
            `;
        }
    };
});