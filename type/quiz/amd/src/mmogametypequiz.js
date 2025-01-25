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
        mmogameid;
        kinduser;
        user;
        url;
        pin;
        labelTimer;
        timeForSendAnswer;
        divDefinition;
        definitionHeight;

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
         * Sets the colors for different UI elements and repairs them if necessary.
         *
         * @param {Array} colors - Array of color codes to be applied.
         */
        setColors(colors) {
            this.repairColors(colors); // Ensure colors are valid

            // Assign specific colors to UI elements
            this.colorDefinition = this.colors[1];
            this.colorScore = this.colors[2];
            this.colorCopyright = this.colors[3];
            this.colorScore2 = this.colors[4];
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

        /**
         * Processes the response for a game attempt, updating the state and UI.
         *
         * @param {Object} json - The server response containing attempt data.
         */
        processGetAttempt(json) {
            // Calculate time difference and set up the clock
            this.computeDifClock(json.time, json.timestart, json.timeclose);

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
            this.updateButtonsAvatar(1, json.avatar, json.nickname);

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
        }

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

        /**
         * Handles the timeout scenario by disabling inputs and sending timeout data.
         */
        onTimeout() {
            this.labelTimer.innerHTML = ''; // Clear the timer display
            this.disableInput(); // Prevent further user input
            this.sendTimeout(); // Notify the server about the timeout
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
                this.createScreenHorizontal(disabled);
            }

            // Display the current score
            this.showScore(json);
        }

        /**
         * Creates a vertical layout for the quiz screen.
         *
         * @param {boolean} disabled - Whether user input should be disabled.
         */
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

        /**
         * Creates a horizontal layout for the quiz screen.
         *
         * @param {boolean} disabled - Whether user input should be disabled.
         */
        createScreenHorizontal(disabled) {
            let maxHeight = this.areaHeight - 2 * this.padding;

            if (!this.hideSubmit) {
                maxHeight -= this.iconSize + this.padding; // Reserve space for submit button
            }

            const width = Math.round((this.areaWidth - this.padding) / 2);
            for (let step = 1; step <= 2; step++) {
                let defSize;
                this.fontSize = this.findbest(step === 1 ? this.minFontSize : this.minFontSize / 2, this.maxFontSize,
                    (fontSize) => {
                        defSize = this.createDefinition(0, 0, width - this.padding, true, fontSize);

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
                if (defSize[0] <= width && defSize[1] <= this.areaHeight) {
                    break;
                }
            }

            this.radioSize = Math.round(this.fontSize);
            this.createDefinition(0, 0, width - this.padding, false, this.fontSize);

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
                const label = document.createElement("label");
                label.style.position = "absolute";
                label.style.width = `${width}px`;
                label.style.fontSize = `${fontSize}px`;
                label.style.color = this.getContrastingColor(this.colorBackground);
                label.innerHTML = this.answers[i];
                label.classList.add('mmogame-quiz-multichoice-label');

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
                const item = this.createRadiobox(this.body, checkboxSize, this.colorDefinition, this.colorScore, checked, disabled);
                item.style.position = "absolute";
                item.style.left = `${left}px`;
                item.style.top = `${top}px`;
                item.id = "mmogame_quiz_input" + i;

                // Event listeners for interactions
                item.addEventListener('click', () => {
                    if (!item.classList.contains("disabled")) {
                        this.onClickRadio(i, this.colorDefinition, this.colorScore, true);
                    }
                });

                label.addEventListener('click', () => {
                    this.onClickRadio(i, this.colorDefinition, this.colorScore, true);
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
                this.drawRadio(item, this.colorScore, this.colorDefinition); // Update styling
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
            const main = this.createDOMElement('div', {
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

            // Create the main score label
            const scoreLabel = this.createDOMElement('div', {
                parent: this.body,
                classnames: `${prefixclassname}-score`,
                styles: {
                    position: 'absolute',
                    left: `${left}px`,
                    top: `${top + this.iconSize / 4}px`,
                    width: `${this.iconSize / 2}px`,
                    height: `${this.iconSize / 2}px`,
                    lineHeight: `${this.iconSize / 2}px`,
                    textAlign: 'center',
                    color: this.getContrastingColor(this.colorScore),
                },
                attributes: {
                    title: this.getStringM('js_grade'),
                },
            });

            // Create the ranking grade label
            const rankLabel = this.createDOMElement('div', {
                parent: this.body,
                classnames: `${prefixclassname}-rank`,
                styles: {
                    position: 'absolute',
                    left: `${left}px`,
                    top: `${top}px`,
                    width: `${this.iconSize / 2}px`,
                    height: `${this.iconSize / 3}px`,
                    textAlign: 'center',
                    color: this.getContrastingColor(this.colorScore),
                },
                attributes: {
                    title: this.getStringM('js_ranking_grade'),
                },
            });

            // Create the percentage label
            const percentLabel = this.createDOMElement('div', {
                parent: this.body,
                classnames: `${prefixclassname}-percent`,
                styles: {
                    position: 'absolute',
                    left: `${left + this.iconSize / 2}px`,
                    top: `${top}px`,
                    width: `${this.iconSize / 2}px`,
                    height: `${this.iconSize / 3}px`,
                    textAlign: 'center',
                    fontSize: `${this.iconSize / 3}px`,
                    lineHeight: `${this.iconSize / 3}px`,
                    color: rankLabel.style.color,
                },
                attributes: {
                    title: this.getStringM('js_ranking_percent'),
                },
            });

            // Create the additional score label
            let addScoreLabel = null;
            if (createAddScore) {
                addScoreLabel = this.createDOMElement('div', {
                    parent: this.body,
                    classnames: `${prefixclassname}-addscore`,
                    styles: {
                        position: 'absolute',
                        left: `${left + this.iconSize / 2}px`,
                        top: `${top + this.iconSize - this.iconSize / 3}px`,
                        width: `${this.iconSize / 2}px`,
                        height: `${this.iconSize / 3}px`,
                        textAlign: 'center',
                        fontWeight: 'bold',
                        color: color,
                    },
                    attributes: {
                        title: this.getStringM('js_percent'),
                    },
                });
            }

            return {main, scoreLabel, rankLabel, percentLabel, addScoreLabel};
        }

        /**
         * Creates and displays the definition area for the question.
         *
         * @param {number} left - The left position in pixels.
         * @param {number} top - The top position in pixels.
         * @param {number} width - The width of the definition area.
         * @param {boolean} onlyMetrics - Whether to only measure size.
         * @param {number} fontSize - The font size for the definition text.
         * @returns {Array} The width and height of the definition area.
         */
        createDefinition(left, top, width, onlyMetrics, fontSize) {
            width -= 2 * this.padding;

            const definitionDiv = document.createElement("div");
            definitionDiv.style.position = "absolute";
            definitionDiv.style.width = `${width}px`;
            definitionDiv.style.fontSize = `${fontSize}px`;
            definitionDiv.innerHTML = this.definition;

            if (onlyMetrics) {
                this.body.appendChild(definitionDiv);
                const size = [definitionDiv.scrollWidth, definitionDiv.scrollHeight];
                this.body.removeChild(definitionDiv);
                return size;
            }

            // Apply styling and position
            definitionDiv.style.background = this.getColorHex(this.colorDefinition);
            definitionDiv.style.color = this.getContrastingColor(this.colorDefinition);
            definitionDiv.style.left = `${left}px`;
            definitionDiv.style.top = `${top}px`;
            definitionDiv.style.paddingLeft = `${this.padding}px`;
            definitionDiv.style.paddingRight = `${this.padding}px`;

            this.area.appendChild(definitionDiv);

            const height = definitionDiv.scrollHeight + this.padding;
            definitionDiv.style.height = `${height}px`;

            this.definitionHeight = height;
            this.divDefinition = definitionDiv;

            return [definitionDiv.scrollWidth, definitionDiv.scrollHeight];
        }

        /**
         * Displays the current score and ranking on the screen.
         *
         * @param {Object} scoreData - The data containing score, rank, and percentages.
         * @param {string} scoreData.addscore
         * @param {int} scoreData.completedrank
         * @param {int} scoreData.percentcompleted
         * @param {int} scoreData.rank
         * @param {int} scoreData.sumscore
         * @param {string} scoreData.usercode
         */
        showScore({addscore, completedrank, percentcompleted, rank, sumscore, usercode}) {
            // Update total score display
            const scoreText = sumscore !== undefined ? `<b>${sumscore}</b>` : '';
            if (this.labelScore.innerHTML !== scoreText) {
                this.labelScore.innerHTML = scoreText;
                this.autoResizeText(this.labelScore, this.iconSize - 2 * this.padding, this.iconSize / 2, false, 0, 0, 1);
            }

            // Update rank display
            if (this.labelScoreRank.innerHTML !== rank) {
                this.labelScoreRank.innerHTML = rank || '';
                this.autoResizeText(this.labelScoreRank, this.iconSize, this.iconSize / 3, true, 0, 0, 1);
            }

            // Update document title if applicable
            if (usercode !== undefined) {
                document.title = `${usercode} ${name || ''}`;
            }

            // Update additional score
            const addScoreText = addscore !== undefined ? addscore : '';
            if (this.labelAddScore.innerHTML !== addScoreText) {
                this.labelAddScore.innerHTML = addScoreText;
                this.autoResizeText(this.labelAddScore, this.iconSize - 2 * this.padding, this.iconSize / 3, false, 0, 0, 1);
            }

            // Update completed rank display
            if (this.labelScoreRankB.innerHTML !== completedrank) {
                this.labelScoreRankB.innerHTML = completedrank || '';
                this.autoResizeText(this.labelScoreRankB, 0.9 * this.iconSize / 2, this.iconSize / 3, true, 0, 0, 1);
            }

            // Update percentage completed
            const percentageText = percentcompleted !== undefined ? `${Math.round(100 * percentcompleted)}%` : '';
            if (this.labelScoreB.innerHTML !== percentageText) {
                this.labelScoreB.innerHTML = percentageText;
                this.autoResizeText(this.labelScoreB, 0.8 * this.iconSize / 2, this.iconSize / 3, true, 0, 0, 1);
            }
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

    };
    });