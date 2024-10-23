const state_play = 1
const state_last = 1

class mmogame_quiz_aduel extends mmogame_quiz {
    constructor() {
        super();
        this.cIcons = 8;
        this.hasBasBottom = false;
        if (window.innerWidth < window.innerHeight) {
            this.cIcons -= 3;
            this.hasBasBottom = true;
        } else {
             this.cIcons += (this.hasHelp() ? 1 : 0);
        }
        this.isWaitOponent = false;
        this.timeoutMSEC = 2000; // Wait Oponent.
        this.autosubmit = true;
        this.type = 'aduel';
    }

    createIconBar() {
        let i = 0;

        this.removeBodyChilds();
        
        let height = window.innerHeight;

        let copyrightHeight = this.getCopyrightHeight();
        copyrightHeight = 0;
        this.nickNameHeight = Math.round(this.iconSize /  3);

        this.areaTop = 2 * this.padding + this.iconSize + this.nickNameHeight;
        this.areaLeft = this.padding;
        this.areaWidth = Math.round(window.innerWidth - 2 * this.padding);
        this.areaHeight = Math.round(window.innerHeight - this.areaTop - copyrightHeight);
        if( this.hasBasBottom) {
            this.areaHeight -= this.iconSize + 2 * this.padding;
        }

        this.buttonAvatarLeft = this.padding + i * (this.iconSize + this.padding);
        this.buttonAvatarHeight = this.iconSize + this.nickNameHeight;
        this.buttonAvatarTop = this.nickNameHeight + this.iconSize - this.buttonAvatarHeight + this.padding;
        this.createButtonsAvatar(1, Math.round( this.padding + (i++) * (this.iconSize + this.padding)), 2 * this.iconSize + this.padding, this.nickNameHeight);
        this.buttonsAvatar[1].style.top = (this.padding + this.nickNameHeight) + "px";

        this.createDivScorePercent(this.padding + (i++) * (this.iconSize + this.padding), this.padding + this.nickNameHeight, 1, true);

        this.createButtonsAvatar(2, Math.round(this.padding + (i++) * (this.iconSize + this.padding)), 2 * this.iconSize + this.padding, this.nickNameHeight);
        this.buttonsAvatar[2].style.top = (this.padding + this.nickNameHeight) + "px";

        this.createDivScore(Math.round(this.padding + (i++) * (this.iconSize + this.padding)), this.padding + this.nickNameHeight, 2, true);
        this.buttonScore2.style.visibility = "hidden";

        this.createDivTimer(this.padding + (i++) * (this.iconSize + this.padding), this.padding + this.nickNameHeight, this.iconSize);
        if( this.hasBasBottom === false) {
            this.createButtonSound(this.padding + (i++) * (this.iconSize + this.padding), this.padding + this.nickNameHeight);
        } else {
            this.createButtonSound(this.padding, this.areaTop + this.areaHeight);
        }
        let instance = this;
        
        if( this.hasBasBottom === false) {
            this.buttonHighScore = this.createImageButton(this.body, this.padding + i * (this.iconSize + this.padding), this.padding + this.nickNameHeight, this.iconSize, this.iconSize, "mmogame_button_red", 'assets/highscore.svg');

            this.button5050 = this.createImageButton(this.body, this.padding + (i++) * (this.iconSize + this.padding), this.padding + this.nickNameHeight, this.iconSize, this.iconSize, "mmogame_button_red", 'assets/cutred.svg');
        } else {
            this.buttonHighScore = this.createImageButton(this.body, this.padding + (this.iconSize + this.padding), this.areaTop + this.areaHeight, this.iconSize, this.iconSize, "mmogame_button_red", 'assets/highscore.svg');
            
            this.button5050 = this.createImageButton(this.body, this.padding + (this.iconSize + this.padding), this.areaTop + this.areaHeight, this.iconSize, this.iconSize, "mmogame_button_red", 'assets/cutred.svg');
        }
        this.buttonHighScore.style.visibility = 'hidden';
        this.buttonHighScore.addEventListener("click", function(){
            instance.sendGetHighScore();
        })
        this.button5050.addEventListener("click", function(){ instance.sendGetAttempt(false, "tool1"); });
        this.button5050.title = '[LANG_HELP_5050]';

        let left;
        if (this.hasBasBottom === false) {
            left = this.padding + (i++) * (this.iconSize + this.padding);
            this.buttonSkip = this.createImageButton(this.body, left, this.padding + this.nickNameHeight, this.iconSize, this.iconSize, "mmogame_button_red", 'assets/skip.svg');
        } else {
            this.buttonSkip = this.createImageButton(this.body, this.padding + 2 * (this.iconSize + this.padding), this.areaTop + this.areaHeight, this.iconSize, this.iconSize, "mmogame_button_red", 'assets/skip.svg');
        }
        this.buttonSkip.addEventListener("click", function(){ instance.sendAnswer(true, "tool2") });
        this.buttonSkip.title = '[LANG_HELP_SKIP]';

        if (this.hasBasBottom === false) {
            this.buttonWizard = this.createImageButton(this.body, left, this.padding + this.nickNameHeight, this.iconSize, this.iconSize, "mmogame_button_red", 'assets/wizard.svg');
        } else {
            this.buttonWizard = this.createImageButton(this.body, this.padding + 2 * (this.iconSize + this.padding), this.areaTop + this.areaHeight, this.iconSize, this.iconSize, "mmogame_button_red", 'assets/wizard.svg');
        }
        this.buttonWizard.addEventListener("click", function(){instance.sendGetAttempt(false, "tool3");});
        this.buttonWizard.style.visibility = 'hidden';
        this.buttonWizard.title = '[LANG_WIZARD]';

        if (this.hasHelp()) {
            if (this.hasBasBottom === false) {
                this.createButtonHelp(this.padding +  (i++) * (this.iconSize + this.padding), this.padding + this.nickNameHeight);
            } else {
                this.createButtonHelp(this.padding + 3 * (this.iconSize + this.padding), this.areaTop + this.areaHeight);
            }
            this.buttonHelp.addEventListener("click", function(){instance.onClickHelp( this); });
        }

        this.createDivColor(this.body, 0, window.innerHeight - copyrightHeight - 1, window.innerWidth - 1, copyrightHeight, this.getColorGray( this.colorScore2));
        this.vertical = this.areaHeight > this.areaWidth;
 
        this.maxImageWidth = (this.vertical ? this.areaWidth : this.areaWidth / 2);
        this.maxImageHeight = (this.vertical ? this.areaHeight / 2 : this.areaWidth);
    }

    updateButtonTool(btn, tool) {
        btn.style.visibility = tool != undefined && tool != 0 ? "hidden" : "visible";
    }

    onServerGetAttempt(json, param) {
        if (this.buttonHighScore != undefined) {
            this.buttonHighScore.style.visibility = 'hidden';
        }
        if (json.dataroot != undefined) {
            this.dataroot = json.dataroot;
        }
        this.computeDifClock(json);
        this.timefastjson = json.timefastjson;
        this.fastjson = json.fastjson;

		if (json.name != undefined) {
			window.document.title = json.name;
        }

        if (json.helpurl != undefined) {
            this.helpUrl = json.helpurl;
        }

        this.correct = undefined
        if (json.state == 0) {
            json.qtype = '';
            super.onServerGetAttempt(json, param);
            this.show_score(json);
            this.button5050.style.visibility = 'hidden';
            this.buttonSkip.style.visibility = 'hidden' ;           
            if (this.btnSubmit != undefined) {
                this.btnSubmit.style.visibility = "hidden";
            }
            this.buttonsAvatar[2].style.visibility = 'hidden';            
            return this.createDivMessageStart('[LANGM_WAIT_TO_START]');
        }

        this.state = json.state;
        
        // Need for a change on colors.
        if (this.savedColors == undefined || this.savedColors != json.colors) {
            this.savedColors = json.colors;
            this.colors = undefined
        }

        if (this.colors == undefined) {
            this.setColorsString(json.colors);
            this.createIconBar();
        }

        this.updateButtonsAvatar(1, json.avatar, json.nickname);
        this.updateButtonsAvatar(2, json.aduel_avatar, json.aduel_nickname);

        this.updateButtonTool(this.button5050, json.tool1numattempt);
        if (json.tool3 != undefined) {
            json.tool2numattempt = -1;
        } else {
            json.tool3numattempt = -1;
        }
        this.updateButtonTool(this.buttonSkip, json.tool2numattempt);
        this.updateButtonTool(this.buttonWizard, json.tool3numattempt);

        if (param == undefined) {
            param = false;
        }

        this.attempt = json.attempt;

        this.aduel_numattempt = json.aduel_numattempt;
        this.aduel_player = json.aduel_player;
        this.aduel_score = json.aduel_score;
        this.aduel_rank = json.aduel_rank;

        if (json.errorcode != undefined && json.errorcode == 'aduel_no_rivals' || json.attempt == 0) {
            if (param == false) {
                this.waitOponent();
            }
            this.hideTools();
            let instance = this;
            this.show_score( json);
            this.timeout = setTimeout(function(){
                instance.sendGetAttempt( true)}, this.timeoutMSEC);
            return;
        }

        this.isWaitOponent = false;

        this.qtype = json.qtype;
        if( json.qtype == 'multichoice') {
            this.answers = [];
            this.answersID = [];
            for (let i = 1; i <= json.answers; i++) {
                this.answersID.push(json["answerid_" + i]);
                this.answers.push(this.repairP( json["answer_" + i]));
            }
        }
        this.answer = json.answer != undefined ? json.answer : null;
        this.definition = this.repairP( json.definition);
        if (json.aduel_attempt != undefined) {
            this.definition = json.aduel_attempt + ". " + this.definition;
        }
        this.single = json.single;
        this.autosave = json.autosave != 0;
        this.errorcode = json.errorcode;

        this.readJsonFiles( json);
        this.createScreen(json, false);
        if (this.btnSubmit != undefined) {
            this.btnSubmit.style.visibility = "hidden";
        }

        this.updateLabelTimer();
        this.sendFastJSON();
    }

    onTimeout() {
        this.labelTimer.innerHTML = '';
        this.disableInput();
        if (this.btnSubmit != undefined) {
            this.btnSubmit.style.display = 'none';
        }

        this.answer = '';
        this.sendAnswer( true);
        
        this.disableInput();
        
        let btn = super.createImageButton( this.area, this.nextLeft, this.nextTop, 0, this.iconSize, "", 'assets/next.svg', false, 'alt');
        btn.title = '[LANG_NEXT_QUESTION]';
        let instance = this;
        btn.addEventListener("click", function(){
            instance.sendGetAttempt(false);
            instance.area.removeChild(btn);
        });
    }

    waitOponent() {
        if (this.isWaitOponent) {
            return;
        }
        this.updateButtonsAvatar(2, "", "");
        this.createDivMessage('[LANG_WAIT_OPONENT]');
        if (this.labelTimer != undefined) {
            this.labelTimer.innerHTML = "";
        }
    }

    hideTools() {
        this.updateButtonTool(this.button5050, -1);
        this.updateButtonTool(this.buttonSkip, -1);
        this.updateButtonTool(this.buttonWizard, -1);     
    }

    onServerAnswer(json, timeout) {
        if (json.submit == 0) {
            return;
        }

        this.correct = json.correct;

        if (json.correct != undefined) {
            if (this.qtype == "multichoice") {
                this.OnServerAnswer_multichoice(json);
            }
        }

        this.disableInput()
        this.buttonHighScore.style.visibility = 'visible';  

        this.isWaitOponent = false;
        if (this.aItemAnswer != undefined && json.correct != undefined) {
            let aCorrect = json.correct.split(",");
            for (let i = 0; i < this.aItemAnswer.length; i++) {
                let color = aCorrect.includes(this.answersID[i]) ? this.colorCorrectAnswer : this.colorWrongAnswer;
                this.aItemAnswer[i].classList.add("disabled");
            }
        }
        if (this.divShortAnswer != undefined) {
            this.divShortAnswer.disabled = true;
        }
        if (this.btnSubmit != undefined) {
            this.body.removeChild(this.btnSubmit);
            this.btnSubmit = undefined;
        }

        this.showCorrectAnswer(json)

        let btn = super.createImageButton(this.area, this.nextLeft, this.nextTop, 0, this.iconSize, "", 'assets/next.svg', false, 'alt');
        btn.title = '[LANG_NEXT_QUESTION]';
        let instance = this;
        btn.addEventListener("click", function(){
            instance.sendGetAttempt(false); 
            instance.area.removeChild(btn);            
        })

        this.show_score(json);

        this.timeclose = 0;

        if (this.aduel_player == 1) {
            if (json.attempt == 0) {
                this.timeclose = 0;
                this.waitOponent()
            }

            this.show_score( json);
        }

        if (this.button5050 != undefined) {
            this.button5050.style.visibility = 'hidden';
            this.buttonSkip.style.visibility = 'hidden';
        }
    }

    sendAnswer(submit, subcommand) {
        if( this.correct == undefined) {
            super.sendAnswer(submit, subcommand);
        }
    }

    OnServerAnswer_multichoice(json) {
        json.aduel_correct += "";
		let found_correct = false;

        let aduel_answers = json.aduel_player == 2 && json.aduel_useranswer != null ? json.aduel_useranswer.split(",") : '';
        let aCorrect = json.correct.split(",");
        for (let i = 0; i < this.answersID.length; i++) {
            if (this.answersID[i] == '') {
                continue;
            }

            let label = this.aItemLabel[i];

            let iscorrect1, iscorrect2;

            let iscorrect = aCorrect.includes(this.answersID[i]);

            if (this.aItemAnswer[i].classList.contains("checked")) { 
                iscorrect1 = aCorrect.includes(this.answersID[i]);
            }
			if (iscorrect1) {
				found_correct = true;
			}

            if (aduel_answers.includes(this.answersID[i])) {
                iscorrect2 = aCorrect.includes(this.answersID[i]);
            }

            if (iscorrect == false && iscorrect1 == undefined && iscorrect2 == undefined) {
                continue;
            }

            let width = this.labelWidth;
			let height = this.aItemLabel[i].scrollHeight;

            if (iscorrect) {
                this.aItemLabel[i].innerHTML = '<b><u>' + this.aItemLabel[i].innerHTML + '</b></u>';           
            }
            let move = (iscorrect2 != undefined ? 2 : 1) * this.radioSize;
            if (iscorrect1 == undefined && iscorrect2 == undefined) {
                move = 0;
            }
            width -= move;

            if (move != 0) {
                label.style.left = ( parseInt( label.style.left) + move) + "px";
            }
            this.aItemLabel[i].style.width = width + "px";
            this.autoResizeText(this.aItemLabel[i], width, height, true, this.minFontSize, this.maxFontSize, 0.5);

            if (iscorrect1 != undefined) {
                let t = parseInt(this.aItemAnswer[i].style.top);
                let div = this.createDiv(this.area, this.aItemCorrectX[i], t, this.radioSize, this.radioSize);
                div.title = iscorrect1 ? '[LANG_CORRECT_ANSWER]' : '[LANG_WRONG_ANSWER]';
                div.innerHTML = this.getSVGcorrect(this.radioSize, iscorrect1, this.colorScore, this.colorScore);
            }

            if (iscorrect2 != undefined) {
                let t = parseInt(this.aItemAnswer[i].style.top);
                let div = this.createDiv(this.area, this.aItemCorrectX[i] + this.radioSize, t, this.radioSize, this.radioSize);
                div.innerHTML = this.getSVGcorrect(this.radioSize, iscorrect2, this.colorScore2, this.colorScore2);
                div.title = iscorrect2 ? '[LANG_CORRECT_ANSWER]' : '[LANG_WRONG_ANSWER]';
            }
        }

		this.playAudio(found_correct ? this.audioYes : this.audioNo);
    }

    showCorrectAnswer(json) {
        this.timeclose = 0;
        this.updateLabelTimer();

        this.strip = this.createDiv( this.area, this.stripLeft, this.nextTop, this.stripWidth, this.stripHeight);

        if( json.tool2 == undefined || this.aduel_player == 2) {
            this.createImage(this.area, this.stripLeft, this.nextTop, this.buttonsAvatar[1].width, 
            this.buttonsAvatar[1].height, this.buttonsAvatar[1].src);
        }
        if (this.aduel_player == 2 & json.tool2 == undefined) {
            this.createImage(this.area, this.stripLeft + this.iconSize, this.nextTop, this.buttonsAvatar[2].width, this.buttonsAvatar[2].height, 
              this.buttonsAvatar[2].src);
        }

        if (json.iscorrect == 0 && this.isquestion_shortanswer()) {
            let left = this.stripLeft + 4 * this.iconSize + this.iconSize / 2;
            let width = this.stripWidth - 4 * this.iconSize - this.iconSize / 2;
            this.divAnswer = this.createDiv(this.body, left, this.stripTop, width, this.stripHeight);
            this.divAnswer.innerHTML = json.aduel_correct;
            this.divAnswer.style.color = this.getColorContrast(this.colorBackground);
            this.autoResizeText(this.divAnswer, width, this.iconSize, true, this.minFontSize, this.maxFontSize, 0.5);
        }

        this.strip.style.top = this.nextTop + "px";
        let s = this.getSVGcorrect(this.iconSize, json.iscorrect != 0, this.colorScore, this.colorScore);
        if( json.tool2 != undefined && this.aduel_player == 1) {
            s = '';
        }
        if (this.aduel_player == 2 && json.tool2 == undefined) {        
            s += this.getSVGcorrect(this.iconSize, json.aduel_iscorrect != 0, this.colorScore2, this.colorScore2);
        }
        this.strip.innerHTML = s;

        this.strip.style.zIndex = 1;
    }

    show_score(json) {
        let rank = json.rank;
        let rankc = json.completedrank;
        if (rank != undefined && rankc != undefined) {
            if (parseInt(rank) < parseInt(rankc)) {
                json.completedrank = '';
                json.rank = '# ' + rank;
            } else {
                json.rank = '';
                json.completedrank = '# ' + rankc;
            }
        }

        if (json.aduel_player == 2) {
            super.show_score(json);
        } else {
            let s = json.sumscore;
            json.sumscore = this.labelScore.innerHTML;
            super.show_score(json);
            json.sumscore = s;
            s = json.sumscore == undefined ? '' : '<b>' + json.sumscore + '</b>';
            if (this.labelScore.innerHTML != s) {
                this.labelScore.innerHTML = s;
                this.autoResizeText(this.labelScore, 0.8 * this.iconSize / 2, this.iconSize / 2, false, 0, 0, 1);
            }
        }

        if (json.aduel_player == 1 || json.aduel_player == undefined) {
            this.labelScore2.style.visibility = "hidden";
            this.labelScoreRank2.style.visibility = "hidden";
            this.buttonScore2.style.visibility = "hidden";
            this.labelAddScore2.style.visibility = "hidden";
        } else {
            this.labelScore2.style.visibility = "visible";
            this.labelScoreRank2.style.visibility = "visible";
            this.buttonScore2.style.visibility = "visible";
            this.labelAddScore2.style.visibility = "visible";

            let rank = json.aduel_rank;
            let score = json.aduel_score;
          
            if (json.aduel_rank != undefined && json.aduel_completedrank != undefined) {
                let rank1 = parseInt(json.aduel_rank);
                let rank2 = parseInt(json.aduel_completedrank);
                if (rank1 <= rank2) {
                    rank = '#' + rank1;
                    score = json.aduel_score;
                    this.labelScore2.title = '[LANG_GRADE]';
                    this.labelScoreRank2.title = "[LANG_POSITION_GRADE]";
                } else {
                    rank = '#' + rank2;
                    score = Math.round(100 * json.aduel_completedrank) + "%";
                    this.labelScore2.title = "[LANG_PERCENT_OPONENT]";
                    this.labelScoreRank2.title = "[LANG_POSITION_PERCENT]";
                }
            }
            let s = '<b>' + score + '</b>';
            if (this.labelScore2.innerHTML != s) {
                this.labelScore2.innerHTML = s;
                this.autoResizeText(this.labelScore2, this.iconSize - 2 * this.padding, this.iconSize / 2, false, 0, 0, 1);
            }
            this.labelScoreRank2.innerHTML = rank;
            this.labelScoreRank2.style.lineHeight = (this.iconSize / 3) + "px";
            this.autoResizeText(this.labelScoreRank2, 0.5 * this.iconSize, this.iconSize / 3, true, 0, 0, 1);

            this.labelAddScore2.innerHTML = json.aduel_addscore == undefined ? '' : json.aduel_addscore;
            this.autoResizeText(this.labelAddScore2, this.iconSize, this.iconSize / 3, true, 0, 0, 1);
        }

        json.rank = rank;
        json.completedrank = rankc;
    }

    onServerFastJson(response) {
        if (response == '') {
            return this.sendFastJSON();
        }

        let a = response.split('-');  //state,timefastjson
        let newstate = a.length > 0 ? parseInt(a[0]) : 0;
        let newTimeFastJSON = a.length > 1 ? a[1] : 0;

        if (this.timefastjson == null) {
            this.timefastjson = 0;
        }

        if (newstate != this.state || newTimeFastJSON != this.timefastjson) {
            return this.sendGetAttempt();
        }

        this.sendFastJSON();
    }

    sendGetHighScore() {
        var xmlhttp = new XMLHttpRequest();
        var instance = this;
        xmlhttp.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {
                let json = JSON.parse(this.responseText)
                instance.createScreenHighScore( json);
            }
        }

        xmlhttp.open("POST", this.url, true);
        xmlhttp.setRequestHeader("Content-Type", "application/json");
        var data = JSON.stringify({ "command": "gethighscore", "mmogameid": this.mmogameid, "pin" : this.pin,
			'kinduser': this.kinduser, "user": this.auserid, "count" : 10});
        xmlhttp.send( data);
    }

    createScreenHighScore(json) {
        if (this.highScore != undefined) {
            this.body.removeChild(this.highScore);
            this.highScore = undefined;
            this.strip.style.visibility = 'visible';
            this.strip.style.zIndex = 1;
            return;
        }

        this.removeDivMessage();

        if( this.vertical) {
            this.createScreenVerticalHighScore(json);
        } else {
            this.createScreenHorizontalHighScore(json);
        }
    }

    createScreenHorizontalHighScore(json) {
        this.createScreenVerticalHighScore(json);
    }

    createScreenVerticalHighScore(json) {
        if (json.count == 0) {
            return;
        }

        let instance = this;

        if (this.strip != undefined) {
            this.strip.style.visibility = 'hidden';
        }

        this.highScore = this.createDivColor( this.body, this.areaLeft, this.areaTop, this.areaWidth - this.padding, this.areaHeight - this.padding, this.getColorHex( this.colorBackground));

        let top = 2 * this.iconSize;
        let height = this.areaHeight - this.areaTop - this.padding;

        let canvas = document.createElement('canvas');
        canvas.style.left = '0px';
        canvas.style.top = "0px";
        canvas.width  = this.areaWidth;
        canvas.height = this.areaHeight;
        canvas.style.zIndex = 8;
        canvas.style.position = "absolute";        
        this.canvasHighScore = canvas;

        this.highScore.appendChild(canvas);
        
        var ctx = canvas.getContext("2d"); 
        let fontSize = this.minFontSize;
        ctx.font = fontSize + "px sans-serif";

        this.drawHighScore1(json, ctx, fontSize);
    }

    drawHighScore1(json, ctx, fontSize) {
        ctx.textAlign = "center";
        let text1 = ctx.measureText("[LANG_RANKING_ORDER]");
        let width1 = text1.width;
        let text = ctx.measureText("[LANG_GRADE]");
        let width2 = text.width;

        let line = this.areaHeight / json.count;
        let width3 = this.areaWith - width1 - width2;
        let col1 = 0
        let col2 = col1 + width1 + this.padding;
        let col3 = col2 + width2 + this.padding;
        let row = Math.round( 3 * fontSize * 1.2);
        
        let scores = json.scores.split('#');
        let ranks = json.ranks.split('#');  
        let names = json.names.split('#');
        let kinds = json.kinds.split('#');
        let avatars = json.avatars.split('#');

        ctx.fillStyle = this.getColorContrast(this.colorBackground);

        let y = Math.round(fontSize * 1.2);
        ctx.fillText("[LANG_RANKING_ORDER]", col1 + width1 / 2, y);
        ctx.fillText("[LANG_GRADE]", col2 + width2 / 2, y);
        ctx.textAlign = "left";
        ctx.fillText("[LANGM_NAME]", col3, y)
        for (let i = 1; i <= json.count; i++) { 
            y += row / 2 + this.padding;

            ctx.textAlign = "center";
            ctx.fillText(ranks[i - 1], col1 + width1 / 2, y);

            ctx.textAlign = "center";
            ctx.fillText(scores[i - 1], col2 + width2 / 2, y);

            ctx.textAlign = "left";
            ctx.fillText(names[i - 1], col3 + row, y);

            this.createImage(this.highScore, col3, y - row / 2, row - this.padding, row - this.padding, 'assets/avatars/' + avatars[i - 1]);

            y += row / 2- this.padding;
        }

        return top;
    }

    drawHighScore2(json, ctx, fontSize, left, top) {
        ctx.textAlign = "center";
        let text = ctx.measureText("[LANGM_DATE]");
        let width1 = text.width;
        text = ctx.measureText("[LANG_GRADE]");
        let width2 = text.width;

        let line = this.areaHeight / json.count;
        let width3 = this.areaWith - width1 - width2;
        let col1 = 0;
        let col2 = col1 + width1 + this.padding;
        let col3 = col2 + width2 + this.padding;
        let row = Math.round( 3 * fontSize * 1.2);

        let scores = json.scores.split('#');
        let ranks = json.ranks.split('#');     
        let names = json.names.split('#');
        let avatars = json.avatars.split('#');

        ctx.fillStyle = this.getColorContrast(this.colorBackground);
        ctx.fillText("[LANGM_DATE]", col1 + width1 / 2, top + row);
        ctx.fillText("[LANGM_GRADE]", col2 + width2 / 2, top + row);
        ctx.textAlign = "left";
        ctx.fillText("[LANGM_NAME]", col3, top + row);

        for (let i = 1; i <= json.count; i++) {
            let y = top + (i + 1) * row;
            ctx.textAlign = "center";
            ctx.fillText(ranks[i - 1], col1 + width1 / 2, y);

            ctx.textAlign = "center";
            ctx.fillText(scores[i - 1], col2 + width2 / 2, y);

            ctx.textAlign = "left";
            ctx.fillText(names[i - 1], col3 + row, y);

            this.createImage(this.body, col3 + this.padding, this.areaTop + y - row, row - this.padding, row - this.padding, 'assets/avatars/' + avatars[i - 1]);
        }
    }

    showHelpScreen(div, width, height) {
        div.innerHTML = `
<div>[LANG_ADUEL_HELP]</div>

<table border=1>
    <tr>
        <td><center>
            <img height="90" src="assets/cutred.svg" alt="" />
        </td>
        <td>[LANG_ADUEL_CUT]</td>
        <td><center>
            <img height="90" src="assets/skip.svg" alt="" />
        </td>
        <td>[LANG_ADUEL_SKIP]</td>
        <td><center>
            <img height="90" src="assets/wizard.svg" alt="" />
        </td>
        <td>[LANG_ADUEL_WIZARD]</td>
    </tr>

    <tr>
        <td><center>
            <img height="90" src="type/quiz/assets/aduel/example1.png" alt="" />
        </td><center>

        <td>[LANG_ADUEL_EXAMPLE1]</td>
        <td><center>
            <img height="90" src="type/quiz/assets/aduel/example2.png" alt="" />
        </td>

        <td>[LANG_ADUEL_EXAMPLE2]</td>
    </tr>
</table>        
        `;
    }
}
