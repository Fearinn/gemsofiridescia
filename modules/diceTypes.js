var BgaDie6 = /** @class */ (function () {
  /**
   * Create the die type.
   *
   * @param settings the die settings
   */
  function BgaDie6(settings) {
    var _a;
    this.settings = settings;
    this.facesCount = 6;
    this.borderRadius =
      (_a =
        settings === null || settings === void 0
          ? void 0
          : settings.borderRadius) !== null && _a !== void 0
        ? _a
        : 0;
  }
  /**
   * Allow to populate the main div of the die. You can set classes or dataset, if it's informations shared by all faces.
   *
   * @param die the die informations
   * @param element the die main Div element
   */
  BgaDie6.prototype.setupDieDiv = function (die, element) {
    element.classList.add("bga-dice_die6");
    element.style.setProperty(
      "--bga-dice_border-radius",
      "".concat(this.borderRadius, "%")
    );
  };
  return BgaDie6;
})();

const faceToBackgroundPosition = {
  1: { x: 0, y: 100 },
  2: { x: 0, y: 0 },
  3: { x: 100, y: 0 },
  4: { x: 100, y: 100 },
  5: { x: 200, y: 0 },
  6: { x: 200, y: 100 },
};
const gemToMultiplier = { amethyst: 1, citrine: 2, emerald: 3, sapphire: 4 };
const colorToMultiplier = { ff0000: 5, "0000ff": 6, "008000": 7, ffa500: 8 };

function calcBackgroundPosition(face, type, type_arg) {
  let multiplier = 0;

  if (type === "gem") {
    multiplier = gemToMultiplier[type_arg];
  }

  if (type === "mining") {
    multiplier = colorToMultiplier[type_arg];
  }

  const basePosition = faceToBackgroundPosition[face];
  const x = basePosition.x + 100 * 3 * multiplier;
  const y = basePosition.y;

  return `-${x}% -${y}%`;
}

class Die extends BgaDie6 {
  constructor() {
    super();
    this.size = 80;
  }

  setupDieDiv(die, element) {
    super.setupDieDiv(die, element);
    element.style.position = "relative";
    element.classList.add("goi_die");
  }

  setupFaceDiv(die, element, face) {
    element.classList.add("goi_dieFace");
    element.style.backgroundPosition = calcBackgroundPosition(face);
  }
}

class GemDie extends Die {
  constructor() {
    super();
  }

  setupDieDiv(die, element) {
    super.setupDieDiv(die, element);
  }

  setupFaceDiv(die, element, face) {
    super.setupFaceDiv(die, element, face);
    element.style.backgroundPosition = calcBackgroundPosition(
      face,
      die.type,
      die.gem
    );
  }
}

class StoneDie extends Die {
  constructor() {
    super();
  }

  setupDieDiv(die, element) {
    super.setupDieDiv(die, element);
  }

  setupFaceDiv(die, element, face) {
    super.setupFaceDiv(die, element, face);
  }
}

class MiningDie extends Die {
  constructor() {
    super();
  }

  setupDieDiv(die, element) {
    super.setupDieDiv(die, element);
  }

  setupFaceDiv(die, element, face) {
    super.setupFaceDiv(die, element, face);
    element.style.backgroundPosition = calcBackgroundPosition(
      face,
      die.type,
      die.color
    );
  }
}
