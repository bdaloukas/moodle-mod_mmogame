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

define(['mmogamequiz'], function(mmoGameQuiz) {
    return class mmoGameQuizAlone extends mmoGameQuiz {
        constructor(mmogameid, pin, kinduser, user) {
            //super( mmogameid, pin, kinduser, user);
            super();
            this.cIcons = this.hasHelp() ? 5 : 4;
            this.autosave = true;
            this.autosubmit = true;
            this.type = 'alone';
        }

        static init(mmogameid, pin, kinduser, user) {
            return new this(mmogameid, pin, kinduser, user);
        }

        createIconBar() {
            let i = 0;

            this.nickNameHeight = Math.round(this.iconSize / 3);

            this.buttonAvatarLeft = this.padding + i * (this.iconSize + this.padding);
            this.buttonAvatarHeight = Math.round(this.iconSize - this.iconSize / 3);
            this.buttonAvatarTop = this.iconSize - this.buttonAvatarHeight + this.padding;
            this.createButtonsAvatar(1, Math.round(this.padding + (i++) * (this.iconSize + this.padding)));
            this.buttonsAvatar[1].style.top = (this.padding + this.nickNameHeight) + "px";

            this.divNickname = this.createDiv(this.body, this.buttonAvatarLeft, this.padding, this.iconSize, this.buttonAvatarTop);

            this.createDivScorePercent(this.padding + (i++) * (this.iconSize + this.padding),
                this.padding + this.nickNameHeight, 1);

            this.createButtonSound(this.padding + (i++) * (this.iconSize + this.padding), this.padding + this.nickNameHeight);
            let instance = this;
            if (this.hasHelp()) {
                this.createButtonHelp(this.padding + (i++) * (this.iconSize + this.padding), this.padding);
                this.buttonHelp.addEventListener("click", function () {
                    instance.onClickHelp(instance.buttonHelp);
                });
            }

            let copyrightHeight = this.getCopyrightHeight();

            this.areaTop = 2 * this.padding + this.iconSize + this.nickNameHeight;
            this.areaWidth = Math.round(window.innerWidth - 2 * this.padding);

            this.areaHeight = Math.round(window.innerHeight - this.areaTop - copyrightHeight);

            this.createDivColor(this.body, 0, window.innerHeight - copyrightHeight - 1, window.innerWidth - 1, copyrightHeight,
                this.getColorGray(this.colorCopyright));
            this.vertical = this.areaHeight > this.areaWidth;

            this.maxImageWidth = (this.vertical ? this.areaWidth : this.areaWidth / 2);
            this.maxImageHeight = (this.vertical ? this.areaHeight / 2 : this.areaWidth);
        }

        updatePercent(json) {
            if (this.labelPercent !== undefined) {
                let s = json.percentcompleted === undefined ? '' : '<b>' + Math.round(100 * json.percentcompleted) + ' %</b>';
                if (this.labelPercent.innerHTML !== s) {
                    this.labelPercent.innerHTML = s;
                    this.autoResizeText(this.labelPercent, this.iconSize - 2 * this.padding, this.iconSize / 2, false, 0, 0, 1);
                }
            }
        }

        onServerAnswer(json) {
            super.onServerAnswer(json);

            if (json.submit !== 0) {
                this.updatePercent(json);
            }
        }

        onServerFastJson(response) {
            let pos = response.indexOf("-");
            if (pos >= 0) {
                let state = parseInt(response.substr(0, pos));
                let timefastjson = parseInt(response.substr(pos + 1));
                if (timefastjson !== this.timefastjson || state !== this.state) {
                    this.sendGetAttempt();
                    return;
                }
            }

            this.sendFastJSON();
        }

        onServerGetAttempt(json, param) {
            if (json.state === 0 && param === undefined) {
                json.qtype = '';
                super.onServerGetAttempt(json, param);
                this.showScore(json);

                this.createDivMessageStart('[LANGM_WAIT_TO_START]');
                return;
            }

            super.onServerGetAttempt(json, param);
            this.updatePercent(json);
            if (this.btnSubmit !== undefined) {
                this.btnSubmit.style.visibility = "hidden";
            }
        }

        showScore(json) {
            let rank = json.rank;
            let rankc = json.completedrank;
            if (rank !== undefined && rankc !== undefined) {
                if (parseInt(rank) < parseInt(rankc)) {
                    json.completedrank = '';
                    json.rank = '# ' + rank;
                } else {
                    json.rank = '';
                    json.completedrank = '# ' + rankc;
                }
            }

            let s = json.sumscore;
            json.sumscore = this.labelScore.innerHTML;
            super.showScore(json);
            json.sumscore = s;
            s = json.sumscore === undefined ? '' : '<b>' + json.sumscore + '</b>';
            if (this.labelScore.innerHTML !== s) {
                this.labelScore.innerHTML = s;
                this.autoResizeText(this.labelScore, 0.8 * this.iconSize / 2, this.iconSize / 2, false, 0, 0, 1);
            }

            json.rank = rank;
            json.completedrank = rankc;
        }

        showHelpScreen(div) {
            div.innerHTML = `<br>
<div>[LANG_ALONE_HELP]</div><br>

<table border=1>
    <tr>
        <td><img height="90" src="../../assets/aduel/example2.png" alt="" /></td>
        <td>[LANG_ADUEL_EXAMPLE2]</td>
    </tr>
</table>
        `;
        }
    };
});