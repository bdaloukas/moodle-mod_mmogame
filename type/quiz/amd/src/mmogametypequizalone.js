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

            this.createDivScorePercent(this.padding + (i++) * (this.iconSize + this.padding),
                this.padding + this.nickNameHeight, 1);

            this.createButtonSound(this.padding + (i++) * (this.iconSize + this.padding),
                this.padding + this.nickNameHeight);
            let instance = this;
            if (this.hasHelp()) {
                this.createButtonHelp(this.padding + (i++) * (this.iconSize + this.padding), this.padding);
                this.buttonHelp.addEventListener("click", function() {
                    instance.onClickHelp(instance.buttonHelp);
                });
            }

            let copyrightHeight = this.getCopyrightHeight();

            this.areaTop = 2 * this.padding + this.iconSize + this.nickNameHeight;
            this.areaWidth = Math.round(window.innerWidth - 2 * this.padding);

            this.areaHeight = Math.round(window.innerHeight - this.areaTop - copyrightHeight);

            this.createDivColor(this.body, 'mmogame-quiz-alone-color',
                0, window.innerHeight - copyrightHeight - 1, window.innerWidth - 1, copyrightHeight,
                this.getColorGray(this.colorCopyright));
            this.vertical = this.areaHeight > this.areaWidth;
        }

        callSetAnswer() {
            // Clear any existing timeout to prevent duplicate calls
            if (this.timerTimeout !== undefined) {
                clearTimeout(this.timerTimeout);
            }
            this.timerTimeout = undefined;

            let instance = this;
            require(['core/ajax'], function(Ajax) {
                // Define the parameters to be passed to the service
                let params = {
                    mmogameid: instance.mmogameid,
                    kinduser: instance.kinduser,
                    user: instance.user,
                    attempt: instance.attempt,
                    answer: instance.answer !== undefined ? instance.answer : null,
                    answerid: instance.answerid !== undefined ? instance.answerid : null,
                    subcommand: '',
                };
                // Call the service through the Moodle AJAX API
                let ret = Ajax.call([{
                    methodname: 'mmogametype_quiz_set_answer', // Service method name
                    args: params // Parameters to pass
                }]);

                // Handle the server response
                ret[0].done(function(response) {
                    instance.processSetAnswer(JSON.parse(response)); // Trigger further action
                }).fail(function(error) {
                    instance.showError(error);
                    // Return the error if the call fails
                    return error;
                });
            });
        }

        processSetAnswer(json) {
            this.correct = json.correct;

            this.updateScreenAfterAnswer(json);
        }

        onServerFastJson(response) {
            let pos = response.indexOf("-");
            if (pos >= 0) {
                let state = parseInt(response.slice(0, pos));
                let timefastjson = parseInt(response.slice(pos + 1));
                if (timefastjson !== this.timefastjson || state !== this.state) {
                    this.sendGetAttempt({mmogameid: this.mmogameid, kinduser: this.kinduser, user: this.user});
                    return;
                }
            }

            this.sendFastJSON();
        }

        processGetAttempt(json) {
            if (parseInt(json.state) === 0) {
                json.qtype = '';
                if (json.colors !== undefined) {
                    this.setColorsString(json.colors);
                    this.createIconBar();
                }
                this.showScore(json);
                this.createDivMessageStart(this.getStringM('js_wait_to_start'));
                return;
            }

            super.processGetAttempt(json);
            if (this.btnSubmit !== undefined) {
                this.btnSubmit.style.visibility = "hidden";
            }
        }

        showScore(json) {
            if (json.rank !== undefined && json.rankc !== undefined) {
                if (parseInt(json.rank) < parseInt(json.rankc)) {
                    this.completedrank = '';
                    this.rank = '# ' + json.rank;
                } else {
                    this.rank = '';
                    this.completedrank = '# ' + json.rankc;
                }
            }

            let s = json.sumscore;
            super.showScore(json);
            json.sumscore = s;
            s = json.sumscore === undefined ? '' : '<b>' + json.sumscore + '</b>';
            if (this.labelScore.innerHTML !== s) {
                this.labelScore.innerHTML = s;
                this.autoResizeText(this.labelScore, 0.8 * this.iconSize / 2, this.iconSize / 2, false, 0, 0, 1);
            }
        }

        showHelpScreen(div) {
            div.innerHTML = `<br>
<div>` + this.getStringG('js_alone_help') + `</div><br>

<table border=1>
    <tr>
        <td><img height="83" src="assets/aduel/example2.png" alt="" /></td>
        <td>` + this.getStringG('js_aduel_example2') + `</td>
    </tr>
</table>
        `;
        }
    };
});