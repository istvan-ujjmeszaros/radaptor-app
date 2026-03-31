Calendar._DN = new Array
("Vasárnap",
 "Hétfő",
 "Kedd",
 "Szerda",
 "Csütörtök",
 "Péntek",
 "Szombat",
 "Vasárnap");

Calendar._SDN = new Array
("v",
 "h",
 "k",
 "sze",
 "cs",
 "p",
 "szo",
 "v");

Calendar._FD = 1;

Calendar._MN = new Array
("január",
 "február",
 "március",
 "április",
 "május",
 "június",
 "július",
 "augusztus",
 "szeptember",
 "október",
 "november",
 "december");

Calendar._SMN = new Array
("jan",
 "feb",
 "már",
 "ápr",
 "máj",
 "jún",
 "júl",
 "aug",
 "sze",
 "okt",
 "nov",
 "dec");

Calendar._TT = {};
Calendar._TT["INFO"] = "A kalendáriumról";

Calendar._TT["ABOUT"] =
"DHTML dátum/idő kiválasztó\n" +
"(c) dynarch.com 2002-2005 / Author: Mihai Bazon\n" + // don't translate this this ;-)
"GNU LGPL licensz. Lásd a http://gnu.org/licenses/lgpl.html oldalt a részletekhez." +
"\n\n" +
"Dátum választás:\n" +
"- használd a \xab, \xbb gombokat az év kiválasztásához\n" +
"- használd a " + String.fromCharCode(0x2039) + ", " + String.fromCharCode(0x203a) + " gombokat a hónap kiválasztásához\n" +
"- tartsd lenyomva az egérgombot a gyors választáshoz.";
Calendar._TT["ABOUT_TIME"] = "\n\n" +
"Idő választás:\n" +
"- kattintva növelheted az időt\n" +
"- shift-tel kattintva csökkentheted\n" +
"- lenyomva tartva és húzva gyorsabban kiválaszthatod.";

Calendar._TT["PREV_YEAR"] = "Előző év<br>(tartsd nyomva a menühöz)";
Calendar._TT["PREV_MONTH"] = "Előző hónap<br>(tartsd nyomva a menühöz)";
Calendar._TT["GO_TODAY"] = "Mai napra ugrás";
Calendar._TT["NEXT_MONTH"] = "Köv. hónap<br>(tartsd nyomva a menühöz)";
Calendar._TT["NEXT_YEAR"] = "Köv. év<br>(tartsd nyomva a menühöz)";
Calendar._TT["SEL_DATE"] = "Válassz dátumot";
Calendar._TT["DRAG_TO_MOVE"] = "Húzd a mozgatáshoz";
Calendar._TT["PART_TODAY"] = " (ma)";

Calendar._TT["DAY_FIRST"] = "%s legyen a hét első napja";

Calendar._TT["WEEKEND"] = "0,6";

Calendar._TT["CLOSE"] = "Bezár";
Calendar._TT["TODAY"] = "Ma";
Calendar._TT["TIME_PART"] = "(Shift-)Klikk vagy húzás<br>az érték változtatásához";

Calendar._TT["DEF_DATE_FORMAT"] = "%Y-%m-%d";
Calendar._TT["TT_DATE_FORMAT"] = "%b %e, %a";

Calendar._TT["WK"] = "hét";
Calendar._TT["TIME"] = "idő:";
