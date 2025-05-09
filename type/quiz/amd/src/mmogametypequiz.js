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
        timeForSendFastJson = 3000;
        timefastjson;
        type;

        // Colors.
        colorScore;
        colorScore2;

        /**
         * Base class for Quiz mmmogame
         *
         * @module mmogametype_quiz
         * @copyright 2024 Vasilis Daloukas
         * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
         */

        constructor(type) {
            super();
            this.type = type;
        }

        /**
         * Initializes the game by loading required audio assets.
         */
        openGame() {
            super.openGame(); // Call the parent class method

            // Optimized Audio Loading
            this.audioYes = new Audio('assets/yes1.mp3');
            this.audioYes.load();
            this.audioNo = new Audio('assets/no1.mp3');
            this.audioNo.load();
        }

        /**
         * Creates a vertical layout for the quiz screen.
         *
         * @param {number}left
         * @param {number}top
         * @param {number}width
         * @param {boolean}onlyMetrics
         * @param {number}fontSize
         * @param {boolean} disabled - Whether user input should be disabled.
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
            const aChecked = this.answer;
            const fragment = document.createDocumentFragment(); // ✅ Batch DOM updates

            const retSize = [0, 0];
            const checkboxSize = Math.round(fontSize);
            this.aItemAnswer = Array(n);
            this.aItemLabel = Array(n);
            this.aItemCorrectX = new Array(n);

            // Iterate over each answer
            for (let i = 0; i < n; i++) {
                const label = this.createDOMElement('label', {
                    parent: null,
                    classnames: 'mmogame-quiz-label' + i,
                    styles: {
                        position: 'absolute',
                        width: `${width - fontSize - this.padding}px`,
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
                label.style.left = `${left + fontSize + this.padding}px`;
                label.style.top = `${top}px`;
                label.style.align = "left";
                label.style.color = this.getContrastingColor(this.colorBackground);

                // Create the checkbox
                const checked = aChecked !== undefined && aChecked !== null && aChecked.includes(this.answersID[i]);
                const item = this.createRadiobox(this.body, checkboxSize, this.colorBackground2, this.colorScore,
                    checked, disabled);
                item.style.position = "absolute";
                item.style.left = `${left}px`;
                item.style.top = `${top}px`;
                item.id = "mmogame_quiz_input" + i;

                // Event listeners for interactions
                item.addEventListener('click', () => {
                    if (!item.classList.contains("disabled")) {
                        this.onClickRadio(i, this.colorBackground2, this.colorScore);
                    }
                });

                label.addEventListener('click', () => {
                    this.onClickRadio(i, this.colorBackground2, this.colorScore);
                });

                fragment.appendChild(item);
                this.area.appendChild(label);

                const heightLabel = label.scrollHeight;
                if (heightLabel > fontSize) {
                    item.style.top = Math.round(top + (heightLabel - fontSize) / 2) + "px";
                }

                this.aItemAnswer[i] = item;
                this.aItemCorrectX[i] = left + fontSize + this.padding;
                this.aItemLabel[i] = label;

                // Adjust positioning
                top += Math.max(label.scrollHeight, fontSize) + this.padding;
            }

            this.area.appendChild(fragment); // Batch insert into DOM

            return onlyMetrics ? retSize : top;
        }

        /**
         * Handles radio button click events for answers.
         *
         * @param {number} index - The index of the clicked radio button.
         * @param {string} colorBack - The background color for the radio button.
         * @param {string} color - The color for the radio button when selected.
         */
        onClickRadio(index, colorBack, color) {
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

            // Send the answer
            this.callSetAnswer();
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
         * Disables all answer inputs to prevent further interaction.
         */
        disableInput() {
            if (!this.aItemAnswer) {
                return;
            }

            this.aItemAnswer.forEach(item => {
                item.classList.add("disabled");
                item.setAttribute("disabled", "true"); // Ensuring proper disabling
                this.drawRadio(item, this.colorScore, this.colorBackground2); // Update styling
            });
        }
        /**
         * Sends periodic fast JSON updates to the server.
         */
        /**
         * Improved AJAX request using Fetch API instead of XMLHttpRequest
         */
        async sendFastJSON() {
            // Clear any existing timeout before setting a new one
            if (this.timeoutFastJSON !== undefined) {
                clearTimeout(this.timeoutFastJSON);
            }

            this.timeoutFastJSON = setTimeout(async() => {
                try {
                    // Create URL-encoded form data
                    const formData = new URLSearchParams();
                    formData.append("fastjson", this.fastjson.toString());
                    formData.append("type", this.type);

                    // Send POST request with application/x-www-form-urlencoded format
                    const response = await fetch(`${this.url}/state.php`, {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: formData.toString(),
                    });

                    // Check if the response is successful
                    if (!response.ok) {
                        throw new Error(`Server responded with status ${response.status}`);
                    }

                    // Read server response
                    const data = await response.text();
                    await this.processFastJson(data);
                } catch (error) {
                    this.showError('Error sending Fast JSON:', error);
                }
            }, this.timeForSendFastJson);
        }


        getStringT(name) {
            return M.util.get_string(name, 'mmogametype_quiz');
        }

        /**
         * Creates a percentage-based score display using createDOMElement.
         *
         * @param {any} parent
         * @param {string} prefixclassname
         * @param {number} left - The left position in pixels.
         * @param {number} top - The top position in pixels.
         * @param {boolean} createAddScore
         * @param {number} colorBackground
         * */
        createDivScorePercent(parent, prefixclassname, left, top,
                              createAddScore, colorBackground) {
            const colorText = this.getContrastingColor(colorBackground);
            // Create the main button container
            const divMain = this.createDOMElement('div', {
                parent: parent,
                classnames: `${prefixclassname}-main`,
                styles: {
                    position: 'absolute',
                    left: `${left}px`,
                    top: `${top}px`,
                    width: `${this.iconSize}px`,
                    height: `${this.iconSize}px`,
                    border: "0px solid " + this.getColorHex(colorBackground),
                    boxShadow: "inset 0 0 0.125em rgba(255, 255, 255, 0.75)",
                    background: this.getColorHex(colorBackground),
                    color: colorText,
                    borderRadius: createAddScore ? `${this.iconSize / 10}px` : `${this.iconSize}px`,
                },
                attributes: {
                    disabled: true,
                    innerHTML: '',
                },
            });

            const cellSize = Math.round(this.iconSize / 2);

            let lblAddScore;
            if (createAddScore) {
                // Create the additional score label (line3)
                lblAddScore = this.createDOMElement('div', {
                    parent: parent,
                    classnames: `${prefixclassname}-addscore`,
                    styles: {
                        position: 'absolute',
                        left: `${left}px`,
                        width: `${cellSize}px`,
                        top: `${top}px`,
                        height: `${cellSize}px`,
                        textAlign: 'center',
                        fontWeight: 'bold',
                        color: colorText,
                    },
                    attributes: {
                        title: this.getStringM('js_add_score'),
                    },
                });
            }

            // Create the ranking grade label (line1)
            const lblRank = this.createDOMElement('div', {
                parent: parent,
                classnames: `${prefixclassname}-rank`,
                styles: {
                    position: 'absolute',
                    left: `${createAddScore ? left + cellSize : left}px`,
                    width: `${createAddScore ? cellSize : 2 * cellSize}px`,
                    top: `${top}px`,
                    height: `${cellSize}px`,
                    textAlign: 'center',
                    color: colorText,
                },
                attributes: {
                    title: this.getStringM('js_ranking'),
                },
            });

            // Create the main score label (line2)
            const lblScore = this.createDOMElement('div', {
                parent: parent,
                classnames: `${prefixclassname}-score`,
                styles: {
                    position: 'absolute',
                    left: `${left}px`,
                    width: `${createAddScore ? cellSize : 2 * cellSize}px`,
                    top: `${top + cellSize}px`,
                    height: `${cellSize}px`,
                    lineHeight: `${cellSize}px`,
                    textAlign: 'center',
                    color: colorText,
                },
                attributes: {
                    title: this.getStringM('js_grade'),
                },
            });

            // Create the percentage label (line2)
            let lblPercent;
            if (createAddScore) {
                lblPercent = this.createDOMElement('div', {
                    parent: parent,
                    classnames: `${prefixclassname}-percent`,
                    styles: {
                        position: 'absolute',
                        left: `${left + cellSize}px`,
                        width: `${cellSize}px`,
                        top: `${top + cellSize}px`,
                        height: `${cellSize}px`,
                        textAlign: 'center',
                        lineHeight: `${cellSize}px`,
                        color: colorText,
                    },
                    attributes: {
                        title: this.getStringM('js_percent'),
                    },
                });
            }

            return {divMain: divMain, lblRank: lblRank, lblScore: lblScore, lblPercent: lblPercent,
                lblAddScore: lblAddScore, cellSize: cellSize};
        }

        showScore(player, score, rank, percent, rankpercent, showPercent) {
            let boldScore = false;
            let boldPercent = false;
            if (rank !== undefined && rankpercent !== undefined) {
                if (parseInt(rankpercent) < parseInt(rank)) {
                    boldScore = true;
                    rank = rankpercent;
                } else if (parseInt(rankpercent) === parseInt(rank)) {
                    boldScore = true;
                    boldPercent = true;
                }
            }

            if (rank !== '') {
                player.lblRank.innerHTML = `#${rank}`;
                this.autoResizeText(player.lblRank, player.cellSize, player.cellSize, false, 0, 0);
            } else {
                player.lblRank.innerHTML = '';
            }

            let s = boldScore ? `<b>${score}</b>` : score;
            if (player.cacheScore !== s) {
                player.cacheScore = s;
                player.lblScore.innerHTML = s;
                const width = showPercent ? player.cellSize : 2 * player.cellSize;
                this.autoResizeText(player.lblScore, width, player.cellSize, false, 0, 0);
            }

            if (showPercent) {
                s = percent === '' ? '' : (boldPercent ? `<b>${Math.round(100 * percent)} </b>` : Math.round(100 * percent)) + '%';
                if (player.lblPercent !== undefined) {
                    if (player.cachePercent !== s) {
                        player.cachePercent = s;
                        player.lblPercent.innerHTML = s;
                        this.autoResizeText(player.lblPercent, player.cellSize - this.padding, player.cellSize, false, 0, 0);
                    }
                }
            }
        }

        /**
         * Creates and displays the definition area for the question.
         *
         * @param {number} left - The left position in pixels.
         * @param {number} top - The top position in pixels.
         * @param {number} width - The width of the definition area.
         * @param {number} height - The height of the definition area.
         * @param {boolean} onlyMetrics - Whether to only measure size.
         * @param {number} fontSize - The font size for the definition text.
         * @param {string} definition
         * @returns {Array} The width and height of the definition area.
         */
        createDefinition(left, top, width, height, onlyMetrics, fontSize, definition) {
            const definitionDiv = this.createDOMElement(
                'div',
                {
                    parent: null,
                    classnames: 'mmogame-quiz-definition',
                    styles: {
                        position: 'absolute',
                        width: `${width}px`,
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
            if (height !== 0) {
                definitionDiv.style.height = `${height}px`;
            }
            definitionDiv.style.padding = `0 ${this.padding}px`;

            this.area.appendChild(definitionDiv);

            return [definitionDiv.scrollWidth, definitionDiv.scrollHeight];
        }

        /**
         * Sends the selected answer to the server using Moodle's AJAX API.
         * @param {string} subcommand
         */
        callSetAnswer(subcommand = '') {
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
                    subcommand: subcommand,
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

        updateNicknameAvatar(player, avatarSrc, nickname, nicknameWidth, nicknameHeight) {
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

        computeBestFontSize(json) {
            let maxHeight, definitionWidth;

            maxHeight = this.areaRect.height - this.iconSize - 3 * this.padding;
            definitionWidth = this.isVertical ? this.areaRect.width : Math.round((this.areaRect.width - this.padding) / 2);

            for (let step = 1; step <= 2; step++) {
                let defSize;
                this.fontSize = this.findbest(step === 1 ? this.minFontSize : this.minFontSize / 2, this.maxFontSize,
                    (fontSize) => {
                        defSize = this.createDefinition(0, 0, definitionWidth, 0, true, fontSize,
                            json);
                        if (defSize[0] > definitionWidth) {
                            return 1;
                        }
                        let ansSize = this.createAnswer(0, 0, definitionWidth, true, fontSize, false);
                        if (ansSize[0] > definitionWidth) {
                            return 1;
                        }
                        if (this.isVertical) {
                            return defSize[1] + ansSize[1] < maxHeight ? -1 : 1;
                        } else {
                            return defSize[1] < maxHeight && ansSize[1] < maxHeight ? -1 : 1;
                        }
                    });
                if (defSize[0] <= definitionWidth && defSize[1] <= this.areaRect.height) {
                    break;
                }
            }
            return [definitionWidth];
        }

        createNextButton(left, top) {
            let btn = super.createImageButton(this.area, 'mmogame-quiz-next',
                left, top, 0, this.iconSize, 'assets/next.svg');
            btn.title = this.getStringM('js_next_question');
            btn.addEventListener("click", () => {
                this.callGetAttempt();
                this.area.removeChild(btn);
            });
        }

        async processFastJson(response) {
            if (response === '') {
                response = await this.callGetState();
            }
            let a = response.split('-'); // Are state,timefastjson.
            let newstate = a.length > 0 ? parseInt(a[0]) : 0;
            let newTimeFastJSON = a.length > 1 ? parseInt(a[1]) : 0;

            if (this.timefastjson === null) {
                this.timefastjson = 0;
            }

            if (newstate !== this.state || newTimeFastJSON !== this.timefastjson) {
                this.removeMessageDivs();
                await this.callGetAttempt();
                return;
            }

            await this.sendFastJSON();
        }

        processGetAttempt(json) {
            this.fastjson = parseInt(json.fastjson);
            this.timefastjson = parseInt(json.timefastjson);

            // Calculate time difference and set up the clock
            this.computeDifClock(json.time);
        }
    };
    });