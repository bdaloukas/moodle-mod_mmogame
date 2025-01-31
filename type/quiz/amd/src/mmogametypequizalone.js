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
/* A
            this.createDivScorePercent('mmogame-quiz-alone', this.padding + (i++) * step,
                this.padding + this.nickNameHeight, this.getConstrastingColor(this.color), true);

            this.createButtonSound(this.padding + (i++) * step,
                this.padding + this.nickNameHeight);
            if (this.hasHelp()) {
                this.createButtonHelp(this.padding + (i++) * step, this.padding);
                this.buttonHelp.addEventListener("click", () => this.onClickHelp(this.buttonHelp));
            }

            let copyrightHeight = this.getCopyrightHeight();

            this.areaRect = {
                // Left: this.padding,
                top: 2 * this.padding + this.iconSize + this.nickNameHeight,
                width: Math.round(window.innerWidth - 2 * this.padding),
                height: Math.round(window.innerHeight - this.areaTop - copyrightHeight),
            };

            this.createDivColor(
                this.body,
                'mmogame-quiz-alone-color',
                0,
                window.innerHeight - copyrightHeight - 1,
                window.innerWidth - 1,
                copyrightHeight,
                this.getColorGray(this.colorCopyright)
            );
*/
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
            this.updateButtonsAvatar(1, this.avatarElement, this.nicknameElement, json.avatar, json.nickname, this.iconSize,
                Math.round(this.iconSize / 3));

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
            this.definition = this.repairP(json.definition);
            this.errorcode = json.errorcode;

            if (json.state !== 0) {
                this.createScreen(json, false);
            }

            this.updateLabelTimer(); // Start or update the timer
            this.sendFastJSON(); // Send fast JSON updates

            if (this.btnSubmit) {
                this.btnSubmit.style.visibility = "hidden";
            }
        }

        showScore({rank, rankc, sumscore}) {
return;
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
                this.labelScore.innerHTML = s;
                this.autoResizeText(this.labelScore, 0.8 * this.iconSize / 2, this.iconSize / 2, false, 0, 0, 1);
            }
        }

        showHelpScreen(div) {
            div.innerHTML = `
                <br>
                <div>${this.getStringG('js_alone_help')}</div><br>

                <table border=1>
                    <tr>
                        <td><img height="83" src="assets/aduel/example2.png" alt="" /></td>
                        <td>${this.getStringG('js_aduel_example2')}</td>
                    </tr>
                </table>
            `;
        }
    };
});