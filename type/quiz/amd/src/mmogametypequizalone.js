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

        processSetAnswer(json) {
            this.correct = json.correct;

            this.playAudio(json.iscorrect !== 0 ? this.audioYes : this.audioNo);

            if (json.correct !== undefined) {
                if (this.qtype === "multichoice") {
                    this.updateScreenAfterAnswerMultichoice();
                }
            }
            this.disableInput();

            if (this.btnSubmit !== undefined) {
                this.body.removeChild(this.btnSubmit);
                this.btnSubmit = undefined;
            }

            let btn = super.createImageButton(this.area, 'mmogame-quiz-next',
                this.nextLeft, this.nextTop, 0, this.iconSize, 'assets/next.svg');
            let instance = this;
            btn.addEventListener("click", function() {
                instance.callGetAttempt();
                instance.area.removeChild(btn);
            });

            this.showScore(json);
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