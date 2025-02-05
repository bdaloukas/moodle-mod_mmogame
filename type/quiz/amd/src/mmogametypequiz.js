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

define(['mod_mmogame/mmogameui'], function(MmoGameUI) {
    return class MmoGameTypeQuiz extends MmoGameUI {
        kinduser;
        user;
        url;
        pin;
        labelTimer;
        timeForSendAnswer;

        /**
         * Base class for Quiz mmmogame
         *
         * @module mmogametype_quiz
         * @copyright 2024 Vasilis Daloukas
         * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
         */

        constructor() {
            super();
            this.hideSubmit = false;
            this.timeForSendAnswer = 10000;
        }

        /**
         * Initializes the game by loading required audio assets.
         */
        openGame() {
            super.openGame(); // Call the parent class method

            // Load sound effects
            this.audioYes = new Audio('assets/yes1.mp3');
            this.audioYes.load();
            this.audioNo = new Audio('assets/no1.mp3');
            this.audioNo.load();
        }

/* B
        updateLabelTimer() {
            // Exit if labelTimer or timeclose are undefined
            if (!this.labelTimer || !this.timeclose) {
                return;
            }

            // Calculate the remaining time in seconds
            const now = Date.now() / 1000; // Get current time in seconds
            let remainingTime = Math.max(0, this.timeclose - now);

            // If no time is remaining, clear the label and handle timeout
            if (remainingTime === 0) {
                this.labelTimer.innerHTML = '';
                this.onTimeout();
                return;
            }

            // Format the remaining time as mm:ss
            const minutes = Math.floor(remainingTime / 60);
            const seconds = String(Math.floor(remainingTime % 60)).padStart(2, '0');
            this.labelTimer.innerHTML = `${minutes}:${seconds}`;

            // Set a timeout to update the timer every 500ms
            this.timerTimeout = setTimeout(() => this.updateLabelTimer(), 500);
        }
*/
        /**
         * Handles the timeout scenario by disabling inputs and sending timeout data.
         */
/* B
        onTimeout() {
            this.labelTimer.innerHTML = ''; // Clear the timer display
            this.disableInput(); // Prevent further user input
            this.sendTimeout(); // Notify the server about the timeout
        }
*/

        /**
         * Creates a vertical layout for the quiz screen.
         *
         * @param {boolean} disabled - Whether user input should be disabled.
         */
/* B
        createScreenVertical(disabled) {
            const nickNameHeight = Math.round(this.iconSize / 3) + this.padding;
            let maxHeight = this.areaHeight - 4 * this.padding - nickNameHeight;

            if (!this.hideSubmit) {
                maxHeight -= this.iconSize; // Reserve space for the submit button
            }

            const maxWidth = this.areaWidth;

            // Dynamically adjust font size to fit content within constraints
            this.fontSize = this.findbest(this.minFontSize, this.maxFontSize, (fontSize) => {
                const defSize = this.createDefinition(0, 0, maxWidth - 1, true, fontSize);
                if (defSize[0] >= maxWidth) {
                    return 1;
                }

                const ansSize = this.createAnswer(0, 0, maxWidth - 1, true, fontSize, disabled);
                return defSize[1] + ansSize[1] < maxHeight ? -1 : 1;
            });

            this.radioSize = Math.round(this.fontSize);
            const defSize = this.createDefinition(0, 0, maxWidth, false, this.fontSize);

            // Position answers below the definition
            this.nextTop = this.createAnswer(0, defSize[1] + this.padding, maxWidth, false, this.fontSize, disabled);

            if (!this.hideSubmit) {
                // Create and position the submit button
                const space = (this.areaWidth - this.iconSize) / 2;
                this.btnSubmit = this.createImageButton(
                    this.area,
                    'mmogame-quiz-submit',
                    space,
                    this.nextTop,
                    0,
                    this.iconSize,
                    'assets/submit.svg',
                    false,
                    'submit'
                );
                this.btnSubmit.addEventListener('click', () => {
                    this.area.removeChild(this.btnSubmit);
                    this.btnSubmit = undefined;
                    this.sendAnswer();
                });
            }

            // Adjust strip dimensions
            this.stripLeft = this.padding;
            this.stripWidth = 2 * this.iconSize;
            this.stripHeight = this.iconSize;
        }
*/
        createAnswer(left, top, width, onlyMetrics, fontSize, disabled) {
            return this.createAnswerMultichoice(left, top, width, onlyMetrics, fontSize, disabled);
        }

        /**
         * Creates multiple-choice answer options.
         *
         * @param {number} left - The left position in pixels.
         * @param {number} top - The top position in pixels.
         * @param {number} width - The maximum width available for answers.
         * @param {boolean} onlyMetrics - Whether to only calculate size metrics.
         * @param {number} fontSize - The font size for answer text.
         * @param {boolean} disabled - Whether the answers are disabled.
         * @returns {number} The total height used by the answer options.
         */
        createAnswerMultichoice(left, top, width, onlyMetrics, fontSize, disabled) {
            const n = this.answers ? this.answers.length : 0;
            const aChecked = this.answer?.split(",").filter(Boolean) || [];
            const retSize = [0, 0];
            const checkboxSize = Math.round(fontSize);
            this.aItemAnswer = Array(n);
            this.aItemLabel = Array(n);
            this.aItemCorrectX = new Array(n);

            // Iterate over each answer
            for (let i = 0; i < n; i++) {
                const label = this.createDOMElement('label', {
                    parent: null,
                    classname: 'mmogame-quiz-label' + i,
                    styles: {
                        position: 'absolute',
                        width: `${width}px`,
                        fontSize: `${fontSize}px`,
                        color: this.getContrastingColor(this.colorBackground),
                    }
                });
                label.innerHTML = this.answers[i];

                if (onlyMetrics) {
                    this.area.appendChild(label);
                    const newSize = label.scrollWidth + fontSize + this.padding;
                    retSize[0] = Math.max(retSize[0], newSize);
                    retSize[1] += Math.max(label.scrollHeight, fontSize) + this.padding;
                    this.area.removeChild(label);
                    continue;
                }

                label.htmlFor = "mmogame_quiz_input" + i;
                label.style.left = (left + fontSize + this.padding) + "px";
                label.style.top = top + "px";
                label.style.align = "left";
                label.style.color = this.getContrastingColor(this.colorBackground);

                // Create the checkbox
                const checked = aChecked.includes(this.answersID[i]);
                const item = this.createRadiobox(this.body, checkboxSize, this.colorBackground2, this.colorScore,
                    checked, disabled);
                item.style.position = "absolute";
                item.style.left = `${left}px`;
                item.style.top = `${top}px`;
                item.id = "mmogame_quiz_input" + i;

                // Event listeners for interactions
                item.addEventListener('click', () => {
                    if (!item.classList.contains("disabled")) {
                        this.onClickRadio(i, this.colorBackground2, this.colorScore, true);
                    }
                });

                label.addEventListener('click', () => {
                    this.onClickRadio(i, this.colorBackground2, this.colorScore, true);
                });

                this.area.appendChild(item);
                this.area.appendChild(label);

                this.aItemAnswer[i] = item;
                this.aItemCorrectX[i] = left + fontSize + this.padding;
                this.aItemLabel[i] = label;

                // Adjust positioning
                top += Math.max(label.scrollHeight, fontSize) + this.padding;
            }

            return onlyMetrics ? retSize : top;
        }

        /**
         * Handles radio button click events for answers.
         *
         * @param {number} index - The index of the clicked radio button.
         * @param {string} colorBack - The background color for the radio button.
         * @param {string} color - The color for the radio button when selected.
         * @param {boolean} callSendAnswer - Whether to send the answer immediately.
         */
        onClickRadio(index, colorBack, color, callSendAnswer) {
            if (this.aItemAnswer[index].classList.contains("disabled")) {
                return;
            }

            // Update the selected radio button and deselect others
            this.aItemAnswer.forEach((item, i) => {
                const isDisabled = item.classList.contains("disabled");
                if (i === index) {
                    item.classList.add("checked");
                    this.answerid = this.answersID[i];
                } else {
                    item.classList.remove("checked");
                }

                this.drawRadio(item, isDisabled ? colorBack : 0xFFFFFF, color);
            });

            // Send the answer if autosave is enabled
            if (this.autosave && callSendAnswer) {
                this.callSetAnswer();
            }
        }
/* C
        sendTimeout() {
            let xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = () => {
                if (this.readyState === 4 && this.status === 200) {
                    this.sendGetAttempt();
                }
            };
            xmlhttp.open("POST", this.url, true);

            xmlhttp.setRequestHeader("Content-Type", "application/json");
            let data = JSON.stringify({
                "command": "timeout", "mmogameid": this.mmogameid, "pin": this.pin, 'kinduser': this.kinduser,
                "user": this.user, "attempt": this.attempt
            });
            xmlhttp.send(data);
        }
*/
        /**
         * Generates an SVG for a correct or incorrect icon.
         *
         * @param {number} size - The size of the SVG.
         * @param {boolean} iscorrect - Whether the answer is correct.
         * @param {int} colorCorrect - Color for correct answers.
         * @param {int} colorError - Color for incorrect answers.
         * @returns {string} The SVG markup as a string.
         */
        getSVGcorrect(size, iscorrect, colorCorrect, colorError) {
            if (iscorrect) {
                let c = colorCorrect !== undefined ? this.getColorHex(colorCorrect) : '#398439';
                return "<svg aria-hidden=\"true\" class=\"svg-icon iconCheckmarkLg\" width=\"" + size + "\" height=\"" + size +
                    "\" viewBox=\"0 0 36 36\"><path fill=\"" + c + "\" d=\"m6 14 8 8L30 6v8L14 30l-8-8v-8z\"></path></svg>";
            } else {
                let c = colorError !== undefined ? this.getColorHex(colorError) : '#398439';
                return "<svg width=\"" + size + "\" height=\"" + size +
                    "\" class=\"bi bi-x-lg\" viewBox=\"0 0 18 18\"> <path fill=\"" + c +
                    `" d="M1.293 1.293a1 1 0 0 1 1.414 0L8 6.586l5.293-5.293a1 1 0 1 1 1.414 1.414L9.414 8l5.293 5.293a1 1 0 0 
                1-1.414 1.414L8 9.414l-5.293 5.293a1 1 0 0 1-1.414-1.414L6.586 8 1.293 2.707a1 1 0 0 1 0-1.414z"/></svg>`;
            }
        }


        /**
         * Updates the screen to show the correctness of the user's answers.
         */
        updateScreenAfterAnswerMultichoice() {
            const correctAnswers = this.correct.split(","); // Split correct answer IDs into an array

            for (let i = 0; i < this.answersID.length; i++) {
                const label = this.aItemLabel[i];
                const isChecked = this.aItemAnswer[i].classList.contains("checked");
                const isCorrect = correctAnswers.includes(this.answersID[i]);

                // Skip answers that are neither checked nor correct
                if (!isCorrect && !isChecked) {
                    continue;
                }

                // Adjust label styling and add correct/incorrect icon
                const labelWidth = label.scrollWidth - this.radioSize;
                label.style.left = `${parseInt(label.style.left) + this.radioSize}px`;
                label.style.width = `${labelWidth}px`;

                if (isCorrect) {
                    label.innerHTML = `<b>${label.innerHTML}</b>`;
                }

                const top = parseInt(this.aItemAnswer[i].style.top);
                const feedbackDiv = this.createDiv(this.area, 'mmogame-quiz-correct',
                    this.aItemCorrectX[i], top, this.radioSize, this.radioSize);
                feedbackDiv.innerHTML = this.getSVGcorrect(this.radioSize, isCorrect, this.colorScore, this.colorScore);
            }
        }

        /**
         * Disables all answer inputs to prevent further interaction.
         */
        disableInput() {
            if (!this.aItemAnswer) {
                return;
            }

            for (const item of this.aItemAnswer) {
                item.classList.add("disabled"); // Add 'disabled' class to each input
                this.drawRadio(item, this.colorScore, this.colorBackground2); // Update styling
            }
        }
        /**
         * Sends periodic fast JSON updates to the server.
         */
        sendFastJSON() {
            // Clear existing timeout if any
            if (this.timeoutFastJSON !== undefined) {
                clearTimeout(this.timeoutFastJSON);
            }

            this.timeoutFastJSON = setTimeout(() => {
                const xhr = new XMLHttpRequest();
                xhr.onreadystatechange = () => {
                    this.timeoutFastJSON = undefined;
                    if (xhr.readyState === 4 && xhr.status === 200) {
                        this.onServerFastJson(xhr.response);
                    }
                };

                const url = `${this.url}/state.php`;
                xhr.open("POST", url, true);

                const data = new FormData();
                data.set('fastjson', this.fastjson);
                data.set('type', this.type);

                xhr.send(data); // Send the fast JSON data
            }, this.timeForSendAnswer);
        }

        onClickHelp() {
            if (this.helpUrl !== '') {
                window.open(this.helpUrl, "_blank");
            }
        }

        getStringT(name) {
            return M.util.get_string(name, 'mmogametype_quiz');
        }

        /**
         * Creates a percentage-based score display using createDOMElement.
         *
         * @param {string} prefixclassname
         * @param {number} left - The left position in pixels.
         * @param {number} top - The top position in pixels.
         * @param {number} color
         * @param {boolean} createAddScore
         */
        createDivScorePercent(prefixclassname, left, top, color, createAddScore) {
            // Create the main button container
            this.createDOMElement('div', {
                parent: this.body,
                classnames: `${prefixclassname}-main`,
                styles: {
                    position: 'absolute',
                    left: `${left}px`,
                    top: `${top}px`,
                    width: `${this.iconSize}px`,
                    height: `${this.iconSize}px`,
                    border: "0px solid " + this.getColorHex(0xFFFFFF),
                    boxShadow: "inset 0 0 0.125em rgba(255, 255, 255, 0.75)",
                    color: color,
                },
                attributes: {
                    disabled: true,
                    innerHTML: '',
                },
            });

            let heightLine1, heightLine2, heightLine3;
            if (createAddScore) {
                const maxHeight = this.iconSize;
                heightLine1 = Math.round(maxHeight / 2);
                heightLine2 = heightLine3 = Math.round((maxHeight - heightLine1) / 2);
            } else {
                const maxHeight = this.iconSize;
                heightLine1 = heightLine2 = Math.round(maxHeight / 2);
                heightLine3 = maxHeight - heightLine1 - heightLine2;
            }
            const topLine2 = top + heightLine1;
            const topLine3 = topLine2 + heightLine2;

            // Create the ranking grade label (line1)
            const rankLabel = this.createDOMElement('div', {
                parent: this.body,
                classnames: `${prefixclassname}-rank`,
                styles: {
                    position: 'absolute',
                    left: `${left}px`,
                    width: `${this.iconSize}px`,
                    top: `${top}px`,
                    height: `${heightLine1}px`,
                    textAlign: 'center',
                    color: this.getContrastingColor(this.colorScore),
                },
                attributes: {
                    title: this.getStringM('js_ranking_grade'),
                },
            });

            // Create the main score label (line2)
            const scoreLabel = this.createDOMElement('div', {
                parent: this.body,
                classnames: `${prefixclassname}-score`,
                styles: {
                    position: 'absolute',
                    left: `${left}px`,
                    width: `${this.iconSize / 2}px`,
                    top: `${topLine2}px`,
                    height: `${heightLine2}px`,
                    lineHeight: `${this.iconSize / 2}px`,
                    textAlign: 'center',
                    color: this.getContrastingColor(this.colorScore),
                },
                attributes: {
                    title: this.getStringM('js_grade'),
                },
            });

            // Create the percentage label (line2)
            const percentLabel = this.createDOMElement('div', {
                parent: this.body,
                classnames: `${prefixclassname}-percent`,
                styles: {
                    position: 'absolute',
                    left: `${left + this.iconSize / 2}px`,
                    width: `${this.iconSize / 2}px`,
                    top: `${topLine2}px`,
                    height: `${heightLine2}px`,
                    textAlign: 'center',
                    fontSize: `${this.iconSize / 3}px`,
                    lineHeight: `${this.iconSize / 3}px`,
                    color: rankLabel.style.color,
                },
                attributes: {
                    title: this.getStringM('js_ranking_percent'),
                },
            });

            // Create the additional score label (line3)
            let addScoreLabel = null;
            if (createAddScore) {
                addScoreLabel = this.createDOMElement('div', {
                    parent: this.body,
                    classnames: `${prefixclassname}-addscore`,
                    styles: {
                        position: 'absolute',
                        left: `${left + this.iconSize / 2}px`,
                        width: `${this.iconSize / 2}px`,
                        top: `${topLine3}px`,
                        height: `${heightLine3}px`,
                        textAlign: 'center',
                        fontWeight: 'bold',
                        color: color,
                    },
                    attributes: {
                        title: this.getStringM('js_percent'),
                    },
                });
            }

            return {lblRank: rankLabel, lblScore: scoreLabel, lblPercent: percentLabel, lblAddScore: addScoreLabel,
                heightLine1: heightLine1, heightLine2: heightLine2, heightLine3: heightLine3};
        }


        showScore(player, score, rank, percent, rankpercent, showPercent) {
            let showScore = true;
            if (rank !== undefined && rankpercent !== undefined) {
                if (parseInt(rankpercent) < parseInt(rank)) {
                    showScore = false;
                }
            }

            player.lblRank.innerHTML = `# ${rank}`;
            this.autoResizeText(player.lblRank, 0.8 * this.iconSize, player.heightLine1, false, 0, 0);

            let s = showScore ? `<b>${score}</b>` : '';
            if (player.cacheScore !== s) {
                player.cacheScore = s;
                player.lblScore.innerHTML = s;
                this.autoResizeText(player.lblScore, 0.9 * this.iconSize / 2, player.heightLine2, false, 0, 0);
            }

            s = !showScore || showPercent ? `<b>${percent} %</b>` : '';
            if (player.cachePercent !== s) {
                player.cachePercent = s;
                player.lblPercent.innerHTML = s;
                this.autoResizeText(player.lblPercent, 0.9 * this.iconSize / 2, player.heightLine2, false, 0, 0);
            }
        }

        /**
         * Creates and displays the definition area for the question.
         *
         * @param {number} left - The left position in pixels.
         * @param {number} top - The top position in pixels.
         * @param {number} width - The width of the definition area.
         * @param {boolean} onlyMetrics - Whether to only measure size.
         * @param {number} fontSize - The font size for the definition text.
         * @param {string} definition
         * @returns {Array} The width and height of the definition area.
         */
        createDefinition(left, top, width, onlyMetrics, fontSize, definition) {
            const adjustedWidth = width - 2 * this.padding;

            const definitionDiv = this.createDOMElement(
                'div',
                {
                    parent: null,
                    classnames: 'mmogame-quiz-definition',
                    styles: {
                        position: 'absolute',
                        width: `${adjustedWidth}px`,
                        fontSize: `${fontSize}px`,
                    }
                }
            );
            definitionDiv.innerHTML = definition;

            if (onlyMetrics) {
                document.body.appendChild(definitionDiv);

                const size = [definitionDiv.scrollWidth, definitionDiv.scrollHeight];

                document.body.removeChild(definitionDiv);
                return size;
            }

            // Apply styling and position
            definitionDiv.style.background = this.getColorHex(this.colorBackground2);
            definitionDiv.style.color = this.getContrastingColor(this.colorBackground2);
            definitionDiv.style.left = `${left}px`;
            definitionDiv.style.top = `${top}px`;
            definitionDiv.style.padding = `0 ${this.padding}px`;

            this.area.appendChild(definitionDiv);

            return [definitionDiv.scrollWidth, definitionDiv.scrollHeight];
        }


        /**
         * Sends the selected answer to the server using Moodle's AJAX API.
         */
        callSetAnswer() {
            // Clear existing timeout
            if (this.timerTimeout !== undefined) {
                clearTimeout(this.timerTimeout);
            }

            this.timerTimeout = undefined;

            require(['core/ajax'], (Ajax) => {
                const params = {
                    mmogameid: this.mmogameid,
                    kinduser: this.kinduser,
                    user: this.user,
                    attempt: this.attempt,
                    answer: this.answer || null,
                    answerid: this.answerid || null,
                    subcommand: '',
                };

                Ajax.call([{
                    methodname: 'mmogametype_quiz_set_answer', // API endpoint
                    args: params,
                }])[0].done((response) => {
                    this.processSetAnswer(JSON.parse(response)); // Process the server's response
                }).fail((error) => {
                    this.showError(error); // Handle errors
                });
            });
        }

        /**
         * Sets the colors for different UI elements and repairs them if necessary.
         *
         * @param {Array} colors - Array of color codes to be applied.
         */
        setColors(colors) {
            super.setColors(colors);

            // Assign specific colors to UI elements
            this.colorScore = colors[2];
            this.colorScore2 = colors[4];
        }

        updateAvatarNickname(player, avatarSrc, nickname, nicknameWidth, nicknameHeight) {
            if (avatarSrc === undefined) {
                avatarSrc = "";
            }
            if (nickname === undefined) {
                nickname = "";
            }

            if (avatarSrc === "" && nickname === "") {
                player.avatarElement.style.visibility = 'hidden';
                player.nicknameElement.style.visibility = 'hidden';
                return;
            }

            if (player.cacheNickname !== nickname || nickname === "") {
                player.cacheNickname = nickname;
                let s = nickname;

                if (nickname.length === 0) {
                    const filenameWithExt = avatarSrc.split('/').pop(); // Extract file name
                    // Remove extension, fallback if no extension
                    s = filenameWithExt.split('.').slice(0, -1).join('.') || filenameWithExt;
                }

                s = this.repairNickname(s);
                player.nicknameElement.innerHTML = s;
                player.nicknameElement.style.textAlign = "center";
                player.nicknameElement.style.color = this.getContrastingColor(this.colorBackground);
                this.autoResizeText(player.nicknameElement, nicknameWidth, nicknameHeight, true, 0, 0);
            }

            if (avatarSrc !== player.cacheAvatar) {
                player.avatarElement.src = avatarSrc !== "" ? "assets/avatars/" + avatarSrc : "";
                player.cacheAvatar = avatarSrc;
            }

            player.avatarElement.alt = player.cacheNickname;
            player.avatarElement.style.visibility = 'visible';

            player.nicknameElement.style.visibility = 'visible';
        }

        createScreen(json) {
            if (this.vertical) {
                this.createScreenVertical(json);
            } else {
                this.createScreenHorizontal(json);
            }
        }
    };
    });