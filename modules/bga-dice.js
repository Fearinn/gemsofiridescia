var BgaAnimation = /** @class */ (function () {
  function BgaAnimation(animationFunction, settings) {
    this.animationFunction = animationFunction;
    this.settings = settings;
    this.played = null;
    this.result = null;
    this.playWhenNoAnimation = false;
  }
  return BgaAnimation;
})();
var __extends =
  (this && this.__extends) ||
  (function () {
    var extendStatics = function (d, b) {
      extendStatics =
        Object.setPrototypeOf ||
        ({ __proto__: [] } instanceof Array &&
          function (d, b) {
            d.__proto__ = b;
          }) ||
        function (d, b) {
          for (var p in b)
            if (Object.prototype.hasOwnProperty.call(b, p)) d[p] = b[p];
        };
      return extendStatics(d, b);
    };
    return function (d, b) {
      if (typeof b !== "function" && b !== null)
        throw new TypeError(
          "Class extends value " + String(b) + " is not a constructor or null"
        );
      extendStatics(d, b);
      function __() {
        this.constructor = d;
      }
      d.prototype =
        b === null
          ? Object.create(b)
          : ((__.prototype = b.prototype), new __());
    };
  })();
/**
 * Just use playSequence from animationManager
 *
 * @param animationManager the animation manager
 * @param animation a `BgaAnimation` object
 * @returns a promise when animation ends
 */
function attachWithAnimation(animationManager, animation) {
  var _a;
  var settings = animation.settings;
  var element = settings.animation.settings.element;
  var fromRect = element.getBoundingClientRect();
  settings.animation.settings.fromRect = fromRect;
  settings.attachElement.appendChild(element);
  (_a = settings.afterAttach) === null || _a === void 0
    ? void 0
    : _a.call(settings, element, settings.attachElement);
  return animationManager.play(settings.animation);
}
var BgaAttachWithAnimation = /** @class */ (function (_super) {
  __extends(BgaAttachWithAnimation, _super);
  function BgaAttachWithAnimation(settings) {
    var _this = _super.call(this, attachWithAnimation, settings) || this;
    _this.playWhenNoAnimation = true;
    return _this;
  }
  return BgaAttachWithAnimation;
})(BgaAnimation);
/**
 * Just use playSequence from animationManager
 *
 * @param animationManager the animation manager
 * @param animation a `BgaAnimation` object
 * @returns a promise when animation ends
 */
function cumulatedAnimations(animationManager, animation) {
  return animationManager.playSequence(animation.settings.animations);
}
var BgaCumulatedAnimation = /** @class */ (function (_super) {
  __extends(BgaCumulatedAnimation, _super);
  function BgaCumulatedAnimation(settings) {
    var _this = _super.call(this, cumulatedAnimations, settings) || this;
    _this.playWhenNoAnimation = true;
    return _this;
  }
  return BgaCumulatedAnimation;
})(BgaAnimation);
/**
 * Just does nothing for the duration
 *
 * @param animationManager the animation manager
 * @param animation a `BgaAnimation` object
 * @returns a promise when animation ends
 */
function pauseAnimation(animationManager, animation) {
  var promise = new Promise(function (success) {
    var _a;
    var settings = animation.settings;
    var duration =
      (_a =
        settings === null || settings === void 0
          ? void 0
          : settings.duration) !== null && _a !== void 0
        ? _a
        : 500;
    setTimeout(function () {
      return success();
    }, duration);
  });
  return promise;
}
var BgaPauseAnimation = /** @class */ (function (_super) {
  __extends(BgaPauseAnimation, _super);
  function BgaPauseAnimation(settings) {
    return _super.call(this, pauseAnimation, settings) || this;
  }
  return BgaPauseAnimation;
})(BgaAnimation);
/**
 * Slide of the element from origin to destination.
 *
 * @param animationManager the animation manager
 * @param animation a `BgaAnimation` object
 * @returns a promise when animation ends
 */
function slideAnimation(animationManager, animation) {
  var promise = new Promise(function (success) {
    var _a, _b, _c, _d, _e;
    var settings = animation.settings;
    var element = settings.element;
    var _f = getDeltaCoordinates(element, settings),
      x = _f.x,
      y = _f.y;
    var duration =
      (_a = settings.duration) !== null && _a !== void 0 ? _a : 500;
    var originalZIndex = element.style.zIndex;
    var originalTransition = element.style.transition;
    var transitionTimingFunction =
      (_b = settings.transitionTimingFunction) !== null && _b !== void 0
        ? _b
        : "linear";
    element.style.zIndex = "".concat(
      (_c =
        settings === null || settings === void 0 ? void 0 : settings.zIndex) !==
        null && _c !== void 0
        ? _c
        : 10
    );
    element.style.transition = null;
    element.offsetHeight;
    element.style.transform = "translate("
      .concat(-x, "px, ")
      .concat(-y, "px) rotate(")
      .concat(
        (_d =
          settings === null || settings === void 0
            ? void 0
            : settings.rotationDelta) !== null && _d !== void 0
          ? _d
          : 0,
        "deg)"
      );
    var timeoutId = null;
    var cleanOnTransitionEnd = function () {
      element.style.zIndex = originalZIndex;
      element.style.transition = originalTransition;
      success();
      element.removeEventListener("transitioncancel", cleanOnTransitionEnd);
      element.removeEventListener("transitionend", cleanOnTransitionEnd);
      document.removeEventListener("visibilitychange", cleanOnTransitionEnd);
      if (timeoutId) {
        clearTimeout(timeoutId);
      }
    };
    var cleanOnTransitionCancel = function () {
      var _a;
      element.style.transition = "";
      element.offsetHeight;
      element.style.transform =
        (_a =
          settings === null || settings === void 0
            ? void 0
            : settings.finalTransform) !== null && _a !== void 0
          ? _a
          : null;
      element.offsetHeight;
      cleanOnTransitionEnd();
    };
    element.addEventListener("transitioncancel", cleanOnTransitionCancel);
    element.addEventListener("transitionend", cleanOnTransitionEnd);
    document.addEventListener("visibilitychange", cleanOnTransitionCancel);
    element.offsetHeight;
    element.style.transition = "transform "
      .concat(duration, "ms ")
      .concat(transitionTimingFunction);
    element.offsetHeight;
    element.style.transform =
      (_e =
        settings === null || settings === void 0
          ? void 0
          : settings.finalTransform) !== null && _e !== void 0
        ? _e
        : null;
    // safety in case transitionend and transitioncancel are not called
    timeoutId = setTimeout(cleanOnTransitionEnd, duration + 100);
  });
  return promise;
}
var BgaSlideAnimation = /** @class */ (function (_super) {
  __extends(BgaSlideAnimation, _super);
  function BgaSlideAnimation(settings) {
    return _super.call(this, slideAnimation, settings) || this;
  }
  return BgaSlideAnimation;
})(BgaAnimation);
/**
 * Slide of the element from destination to origin.
 *
 * @param animationManager the animation manager
 * @param animation a `BgaAnimation` object
 * @returns a promise when animation ends
 */
function slideToAnimation(animationManager, animation) {
  var promise = new Promise(function (success) {
    var _a, _b, _c, _d, _e;
    var settings = animation.settings;
    var element = settings.element;
    var _f = getDeltaCoordinates(element, settings),
      x = _f.x,
      y = _f.y;
    var duration =
      (_a =
        settings === null || settings === void 0
          ? void 0
          : settings.duration) !== null && _a !== void 0
        ? _a
        : 500;
    var originalZIndex = element.style.zIndex;
    var originalTransition = element.style.transition;
    var transitionTimingFunction =
      (_b = settings.transitionTimingFunction) !== null && _b !== void 0
        ? _b
        : "linear";
    element.style.zIndex = "".concat(
      (_c =
        settings === null || settings === void 0 ? void 0 : settings.zIndex) !==
        null && _c !== void 0
        ? _c
        : 10
    );
    var timeoutId = null;
    var cleanOnTransitionEnd = function () {
      element.style.zIndex = originalZIndex;
      element.style.transition = originalTransition;
      success();
      element.removeEventListener("transitioncancel", cleanOnTransitionEnd);
      element.removeEventListener("transitionend", cleanOnTransitionEnd);
      document.removeEventListener("visibilitychange", cleanOnTransitionEnd);
      if (timeoutId) {
        clearTimeout(timeoutId);
      }
    };
    var cleanOnTransitionCancel = function () {
      var _a;
      element.style.transition = "";
      element.offsetHeight;
      element.style.transform =
        (_a =
          settings === null || settings === void 0
            ? void 0
            : settings.finalTransform) !== null && _a !== void 0
          ? _a
          : null;
      element.offsetHeight;
      cleanOnTransitionEnd();
    };
    element.addEventListener("transitioncancel", cleanOnTransitionEnd);
    element.addEventListener("transitionend", cleanOnTransitionEnd);
    document.addEventListener("visibilitychange", cleanOnTransitionCancel);
    element.offsetHeight;
    element.style.transition = "transform "
      .concat(duration, "ms ")
      .concat(transitionTimingFunction);
    element.offsetHeight;
    element.style.transform = "translate("
      .concat(-x, "px, ")
      .concat(-y, "px) rotate(")
      .concat(
        (_d =
          settings === null || settings === void 0
            ? void 0
            : settings.rotationDelta) !== null && _d !== void 0
          ? _d
          : 0,
        "deg) scale("
      )
      .concat((_e = settings.scale) !== null && _e !== void 0 ? _e : 1, ")");
    // safety in case transitionend and transitioncancel are not called
    timeoutId = setTimeout(cleanOnTransitionEnd, duration + 100);
  });
  return promise;
}
var BgaSlideToAnimation = /** @class */ (function (_super) {
  __extends(BgaSlideToAnimation, _super);
  function BgaSlideToAnimation(settings) {
    return _super.call(this, slideToAnimation, settings) || this;
  }
  return BgaSlideToAnimation;
})(BgaAnimation);
function shouldAnimate(settings) {
  var _a;
  return (
    document.visibilityState !== "hidden" &&
    !((_a =
      settings === null || settings === void 0 ? void 0 : settings.game) ===
      null || _a === void 0
      ? void 0
      : _a.instantaneousMode)
  );
}
/**
 * Return the x and y delta, based on the animation settings;
 *
 * @param settings an `AnimationSettings` object
 * @returns a promise when animation ends
 */
function getDeltaCoordinates(element, settings) {
  var _a;
  if (!settings.fromDelta && !settings.fromRect && !settings.fromElement) {
    throw new Error(
      "[bga-animation] fromDelta, fromRect or fromElement need to be set"
    );
  }
  var x = 0;
  var y = 0;
  if (settings.fromDelta) {
    x = settings.fromDelta.x;
    y = settings.fromDelta.y;
  } else {
    var originBR =
      (_a = settings.fromRect) !== null && _a !== void 0
        ? _a
        : settings.fromElement.getBoundingClientRect();
    // TODO make it an option ?
    var originalTransform = element.style.transform;
    element.style.transform = "";
    var destinationBR = element.getBoundingClientRect();
    element.style.transform = originalTransform;
    x =
      (destinationBR.left + destinationBR.right) / 2 -
      (originBR.left + originBR.right) / 2;
    y =
      (destinationBR.top + destinationBR.bottom) / 2 -
      (originBR.top + originBR.bottom) / 2;
  }
  if (settings.scale) {
    x /= settings.scale;
    y /= settings.scale;
  }
  return { x: x, y: y };
}
function logAnimation(animationManager, animation) {
  var settings = animation.settings;
  var element = settings.element;
  if (element) {
    console.log(
      animation,
      settings,
      element,
      element.getBoundingClientRect(),
      element.style.transform
    );
  } else {
    console.log(animation, settings);
  }
  return Promise.resolve(false);
}
var __assign =
  (this && this.__assign) ||
  function () {
    __assign =
      Object.assign ||
      function (t) {
        for (var s, i = 1, n = arguments.length; i < n; i++) {
          s = arguments[i];
          for (var p in s)
            if (Object.prototype.hasOwnProperty.call(s, p)) t[p] = s[p];
        }
        return t;
      };
    return __assign.apply(this, arguments);
  };
var __awaiter =
  (this && this.__awaiter) ||
  function (thisArg, _arguments, P, generator) {
    function adopt(value) {
      return value instanceof P
        ? value
        : new P(function (resolve) {
            resolve(value);
          });
    }
    return new (P || (P = Promise))(function (resolve, reject) {
      function fulfilled(value) {
        try {
          step(generator.next(value));
        } catch (e) {
          reject(e);
        }
      }
      function rejected(value) {
        try {
          step(generator["throw"](value));
        } catch (e) {
          reject(e);
        }
      }
      function step(result) {
        result.done
          ? resolve(result.value)
          : adopt(result.value).then(fulfilled, rejected);
      }
      step((generator = generator.apply(thisArg, _arguments || [])).next());
    });
  };
var __generator =
  (this && this.__generator) ||
  function (thisArg, body) {
    var _ = {
        label: 0,
        sent: function () {
          if (t[0] & 1) throw t[1];
          return t[1];
        },
        trys: [],
        ops: [],
      },
      f,
      y,
      t,
      g;
    return (
      (g = { next: verb(0), throw: verb(1), return: verb(2) }),
      typeof Symbol === "function" &&
        (g[Symbol.iterator] = function () {
          return this;
        }),
      g
    );
    function verb(n) {
      return function (v) {
        return step([n, v]);
      };
    }
    function step(op) {
      if (f) throw new TypeError("Generator is already executing.");
      while ((g && ((g = 0), op[0] && (_ = 0)), _))
        try {
          if (
            ((f = 1),
            y &&
              (t =
                op[0] & 2
                  ? y["return"]
                  : op[0]
                  ? y["throw"] || ((t = y["return"]) && t.call(y), 0)
                  : y.next) &&
              !(t = t.call(y, op[1])).done)
          )
            return t;
          if (((y = 0), t)) op = [op[0] & 2, t.value];
          switch (op[0]) {
            case 0:
            case 1:
              t = op;
              break;
            case 4:
              _.label++;
              return { value: op[1], done: false };
            case 5:
              _.label++;
              y = op[1];
              op = [0];
              continue;
            case 7:
              op = _.ops.pop();
              _.trys.pop();
              continue;
            default:
              if (
                !((t = _.trys), (t = t.length > 0 && t[t.length - 1])) &&
                (op[0] === 6 || op[0] === 2)
              ) {
                _ = 0;
                continue;
              }
              if (op[0] === 3 && (!t || (op[1] > t[0] && op[1] < t[3]))) {
                _.label = op[1];
                break;
              }
              if (op[0] === 6 && _.label < t[1]) {
                _.label = t[1];
                t = op;
                break;
              }
              if (t && _.label < t[2]) {
                _.label = t[2];
                _.ops.push(op);
                break;
              }
              if (t[2]) _.ops.pop();
              _.trys.pop();
              continue;
          }
          op = body.call(thisArg, _);
        } catch (e) {
          op = [6, e];
          y = 0;
        } finally {
          f = t = 0;
        }
      if (op[0] & 5) throw op[1];
      return { value: op[0] ? op[1] : void 0, done: true };
    }
  };
var __spreadArray =
  (this && this.__spreadArray) ||
  function (to, from, pack) {
    if (pack || arguments.length === 2)
      for (var i = 0, l = from.length, ar; i < l; i++) {
        if (ar || !(i in from)) {
          if (!ar) ar = Array.prototype.slice.call(from, 0, i);
          ar[i] = from[i];
        }
      }
    return to.concat(ar || Array.prototype.slice.call(from));
  };
var AnimationManager = /** @class */ (function () {
  /**
   * @param game the BGA game class, usually it will be `this`
   * @param settings: a `AnimationManagerSettings` object
   */
  function AnimationManager(game, settings) {
    this.game = game;
    this.settings = settings;
    this.zoomManager =
      settings === null || settings === void 0 ? void 0 : settings.zoomManager;
    if (!game) {
      throw new Error(
        "You must set your game as the first parameter of AnimationManager"
      );
    }
  }
  AnimationManager.prototype.getZoomManager = function () {
    return this.zoomManager;
  };
  /**
   * Set the zoom manager, to get the scale of the current game.
   *
   * @param zoomManager the zoom manager
   */
  AnimationManager.prototype.setZoomManager = function (zoomManager) {
    this.zoomManager = zoomManager;
  };
  AnimationManager.prototype.getSettings = function () {
    return this.settings;
  };
  /**
   * Returns if the animations are active. Animation aren't active when the window is not visible (`document.visibilityState === 'hidden'`), or `game.instantaneousMode` is true.
   *
   * @returns if the animations are active.
   */
  AnimationManager.prototype.animationsActive = function () {
    return (
      document.visibilityState !== "hidden" && !this.game.instantaneousMode
    );
  };
  /**
   * Plays an animation if the animations are active. Animation aren't active when the window is not visible (`document.visibilityState === 'hidden'`), or `game.instantaneousMode` is true.
   *
   * @param animation the animation to play
   * @returns the animation promise.
   */
  AnimationManager.prototype.play = function (animation) {
    var _a, _b, _c, _d, _e, _f, _g, _h, _j, _k, _l, _m, _o, _p, _q;
    return __awaiter(this, void 0, void 0, function () {
      var settings, _r;
      return __generator(this, function (_s) {
        switch (_s.label) {
          case 0:
            animation.played =
              animation.playWhenNoAnimation || this.animationsActive();
            if (!animation.played) return [3 /*break*/, 2];
            settings = animation.settings;
            (_a = settings.animationStart) === null || _a === void 0
              ? void 0
              : _a.call(settings, animation);
            (_b = settings.element) === null || _b === void 0
              ? void 0
              : _b.classList.add(
                  (_c = settings.animationClass) !== null && _c !== void 0
                    ? _c
                    : "bga-animations_animated"
                );
            animation.settings = __assign(__assign({}, animation.settings), {
              duration:
                (_g =
                  (_e =
                    (_d = animation.settings) === null || _d === void 0
                      ? void 0
                      : _d.duration) !== null && _e !== void 0
                    ? _e
                    : (_f = this.settings) === null || _f === void 0
                    ? void 0
                    : _f.duration) !== null && _g !== void 0
                  ? _g
                  : 500,
              scale:
                (_l =
                  (_j =
                    (_h = animation.settings) === null || _h === void 0
                      ? void 0
                      : _h.scale) !== null && _j !== void 0
                    ? _j
                    : (_k = this.zoomManager) === null || _k === void 0
                    ? void 0
                    : _k.zoom) !== null && _l !== void 0
                  ? _l
                  : undefined,
            });
            _r = animation;
            return [4 /*yield*/, animation.animationFunction(this, animation)];
          case 1:
            _r.result = _s.sent();
            (_o = (_m = animation.settings).animationEnd) === null ||
            _o === void 0
              ? void 0
              : _o.call(_m, animation);
            (_p = settings.element) === null || _p === void 0
              ? void 0
              : _p.classList.remove(
                  (_q = settings.animationClass) !== null && _q !== void 0
                    ? _q
                    : "bga-animations_animated"
                );
            return [3 /*break*/, 3];
          case 2:
            return [2 /*return*/, Promise.resolve(animation)];
          case 3:
            return [2 /*return*/];
        }
      });
    });
  };
  /**
   * Plays multiple animations in parallel.
   *
   * @param animations the animations to play
   * @returns a promise for all animations.
   */
  AnimationManager.prototype.playParallel = function (animations) {
    return __awaiter(this, void 0, void 0, function () {
      var _this = this;
      return __generator(this, function (_a) {
        return [
          2 /*return*/,
          Promise.all(
            animations.map(function (animation) {
              return _this.play(animation);
            })
          ),
        ];
      });
    });
  };
  /**
   * Plays multiple animations in sequence (the second when the first ends, ...).
   *
   * @param animations the animations to play
   * @returns a promise for all animations.
   */
  AnimationManager.prototype.playSequence = function (animations) {
    return __awaiter(this, void 0, void 0, function () {
      var result, others;
      return __generator(this, function (_a) {
        switch (_a.label) {
          case 0:
            if (!animations.length) return [3 /*break*/, 3];
            return [4 /*yield*/, this.play(animations[0])];
          case 1:
            result = _a.sent();
            return [4 /*yield*/, this.playSequence(animations.slice(1))];
          case 2:
            others = _a.sent();
            return [2 /*return*/, __spreadArray([result], others, true)];
          case 3:
            return [2 /*return*/, Promise.resolve([])];
        }
      });
    });
  };
  /**
   * Plays multiple animations with a delay between each animation start.
   *
   * @param animations the animations to play
   * @param delay the delay (in ms)
   * @returns a promise for all animations.
   */
  AnimationManager.prototype.playWithDelay = function (animations, delay) {
    return __awaiter(this, void 0, void 0, function () {
      var promise;
      var _this = this;
      return __generator(this, function (_a) {
        promise = new Promise(function (success) {
          var promises = [];
          var _loop_1 = function (i) {
            setTimeout(function () {
              promises.push(_this.play(animations[i]));
              if (i == animations.length - 1) {
                Promise.all(promises).then(function (result) {
                  success(result);
                });
              }
            }, i * delay);
          };
          for (var i = 0; i < animations.length; i++) {
            _loop_1(i);
          }
        });
        return [2 /*return*/, promise];
      });
    });
  };
  /**
   * Attach an element to a parent, then play animation from element's origin to its new position.
   *
   * @param animation the animation function
   * @param attachElement the destination parent
   * @returns a promise when animation ends
   */
  AnimationManager.prototype.attachWithAnimation = function (
    animation,
    attachElement
  ) {
    var attachWithAnimation = new BgaAttachWithAnimation({
      animation: animation,
      attachElement: attachElement,
    });
    return this.play(attachWithAnimation);
  };
  return AnimationManager;
})();
function sortFunction() {
  var sortedFields = [];
  for (var _i = 0; _i < arguments.length; _i++) {
    sortedFields[_i] = arguments[_i];
  }
  return function (a, b) {
    for (var i = 0; i < sortedFields.length; i++) {
      var direction = 1;
      var field = sortedFields[i];
      if (field[0] == "-") {
        direction = -1;
        field = field.substring(1);
      } else if (field[0] == "+") {
        field = field.substring(1);
      }
      var type = typeof a[field];
      if (type === "string") {
        var compare = a[field].localeCompare(b[field]);
        if (compare !== 0) {
          return compare;
        }
      } else if (type === "number") {
        var compare = (a[field] - b[field]) * direction;
        if (compare !== 0) {
          return compare * direction;
        }
      }
    }
    return 0;
  };
}
var BGA_DIE4_FACE_NUMBERS = {
  1: [2, 4, 3],
  2: [1, 3, 4],
  3: [1, 4, 2],
  4: [1, 2, 3],
};
var BgaDie4 = /** @class */ (function () {
  /**
   * Create the die type.
   *
   * @param settings the die settings
   */
  function BgaDie4(settings) {
    var _a;
    this.settings = settings;
    this.facesCount = 4;
    this.showValueOverlay =
      (_a =
        settings === null || settings === void 0
          ? void 0
          : settings.showValueOverlay) !== null && _a !== void 0
        ? _a
        : false;
  }
  /**
   * Allow to populate the main div of the die. You can set classes or dataset, if it's informations shared by all faces.
   *
   * @param die the die informations
   * @param element the die main Div element
   */
  BgaDie4.prototype.setupDieDiv = function (die, element) {
    element.classList.add("bga-dice_die4");
  };
  /**
   * Allow to populate a face div of the die. You can set classes or dataset to show the correct die face.
   *
   * @param die the die informations
   * @param element the die face Div element
   * @param face the face number (1-indexed)
   */
  BgaDie4.prototype.setupFaceDiv = function (die, element, face) {
    for (var i = 0; i < 3; i++) {
      var number = document.createElement("div");
      number.classList.add("bga-dice_die-face-number");
      number.dataset.number = "".concat(BGA_DIE4_FACE_NUMBERS[face][i]);
      element.appendChild(number);
    }
  };
  return BgaDie4;
})();
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
/**
 * The abstract stock. It shouldn't be used directly, use stocks that extends it.
 */
var DiceStock = /** @class */ (function () {
  /**
   * @param manager the die manager
   * @param element the stock element (should be an empty HTML Element)
   */
  function DiceStock(manager, element, settings) {
    this.manager = manager;
    this.element = element;
    this.settings = settings;
    this.dice = [];
    this.selectedDice = [];
    this.selectionMode = "none";
    manager.addStock(this);
    element === null || element === void 0
      ? void 0
      : element.classList.add(
          "bga-dice_die-stock" /*, this.constructor.name.split(/(?=[A-Z])/).join('-').toLowerCase()* doesn't work in production because of minification */
        );
    var perspective = this.getPerspective();
    element.style.setProperty(
      "--perspective",
      perspective ? "".concat(perspective, "px") : "unset"
    );
    this.bindClick();
    this.sort =
      settings === null || settings === void 0 ? void 0 : settings.sort;
  }
  /**
   * @returns the dice on the stock
   */
  DiceStock.prototype.getDice = function () {
    return this.dice.slice();
  };
  /**
   * @returns if the stock is empty
   */
  DiceStock.prototype.isEmpty = function () {
    return !this.dice.length;
  };
  /**
   * @returns the selected dice
   */
  DiceStock.prototype.getSelection = function () {
    return this.selectedDice.slice();
  };
  /**
   * @returns the selected dice
   */
  DiceStock.prototype.isSelected = function (die) {
    var _this = this;
    return this.selectedDice.some(function (c) {
      return _this.manager.getId(c) == _this.manager.getId(die);
    });
  };
  /**
   * @param die a die
   * @returns if the die is present in the stock
   */
  DiceStock.prototype.contains = function (die) {
    var _this = this;
    return this.dice.some(function (c) {
      return _this.manager.getId(c) == _this.manager.getId(die);
    });
  };
  /**
   * @param die a die in the stock
   * @returns the HTML element generated for the die
   */
  DiceStock.prototype.getDieElement = function (die) {
    return this.manager.getDieElement(die);
  };
  /**
   * Checks if the die can be added. By default, only if it isn't already present in the stock.
   *
   * @param die the die to add
   * @param settings the addDie settings
   * @returns if the die can be added
   */
  DiceStock.prototype.canAddDie = function (die, settings) {
    return !this.contains(die);
  };
  /**
   * Add a die to the stock.
   *
   * @param die the die to add
   * @param animation a `DieAnimation` object
   * @param settings a `AddDiceettings` object
   * @returns the promise when the animation is done (true if it was animated, false if it wasn't)
   */
  DiceStock.prototype.addDie = function (die, animation, settings) {
    var _this = this;
    var _a;
    if (!this.canAddDie(die, settings)) {
      return Promise.resolve(false);
    }
    var promise;
    // we check if die is in a stock
    var originStock = this.manager.getDieStock(die);
    var index = this.getNewDieIndex(die);
    var settingsWithIndex = __assign(
      { index: index },
      settings !== null && settings !== void 0 ? settings : {}
    );
    var updateInformations =
      (_a = settingsWithIndex.updateInformations) !== null && _a !== void 0
        ? _a
        : true;
    if (
      originStock === null || originStock === void 0
        ? void 0
        : originStock.contains(die)
    ) {
      var element = this.getDieElement(die);
      promise = this.moveFromOtherStock(
        die,
        element,
        __assign(__assign({}, animation), { fromStock: originStock }),
        settingsWithIndex
      );
    } else if (
      (animation === null || animation === void 0
        ? void 0
        : animation.fromStock) &&
      animation.fromStock.contains(die)
    ) {
      var element = this.getDieElement(die);
      promise = this.moveFromOtherStock(
        die,
        element,
        animation,
        settingsWithIndex
      );
    } else {
      var element = this.manager.createDieElement(die);
      promise = this.moveFromElement(
        die,
        element,
        animation,
        settingsWithIndex
      );
    }
    if (
      settingsWithIndex.index !== null &&
      settingsWithIndex.index !== undefined
    ) {
      this.dice.splice(index, 0, die);
    } else {
      this.dice.push(die);
    }
    if (updateInformations) {
      // after splice/push
      this.manager.updateDieInformations(die);
    }
    if (!promise) {
      console.warn("Dicetock.addDie didn't return a Promise");
      promise = Promise.resolve(false);
    }
    if (this.selectionMode !== "none") {
      // make selectable only at the end of the animation
      promise.then(function () {
        var _a;
        return _this.setSelectableDie(
          die,
          (_a = settingsWithIndex.selectable) !== null && _a !== void 0
            ? _a
            : true
        );
      });
    }
    return promise;
  };
  DiceStock.prototype.getNewDieIndex = function (die) {
    if (this.sort) {
      var otherDice = this.getDice();
      for (var i = 0; i < otherDice.length; i++) {
        var otherDie = otherDice[i];
        if (this.sort(die, otherDie) < 0) {
          return i;
        }
      }
      return otherDice.length;
    } else {
      return undefined;
    }
  };
  DiceStock.prototype.addDieElementToParent = function (dieElement, settings) {
    var _a;
    var parent =
      (_a =
        settings === null || settings === void 0
          ? void 0
          : settings.forceToElement) !== null && _a !== void 0
        ? _a
        : this.element;
    if (
      (settings === null || settings === void 0 ? void 0 : settings.index) ===
        null ||
      (settings === null || settings === void 0 ? void 0 : settings.index) ===
        undefined ||
      !parent.children.length ||
      (settings === null || settings === void 0 ? void 0 : settings.index) >=
        parent.children.length
    ) {
      parent.appendChild(dieElement);
    } else {
      parent.insertBefore(dieElement, parent.children[settings.index]);
    }
  };
  DiceStock.prototype.moveFromOtherStock = function (
    die,
    dieElement,
    animation,
    settings
  ) {
    var promise;
    var element = animation.fromStock.contains(die)
      ? this.manager.getDieElement(die)
      : animation.fromStock.element;
    var fromRect = element.getBoundingClientRect();
    this.addDieElementToParent(dieElement, settings);
    this.removeSelectionClassesFromElement(dieElement);
    promise = this.animationFromElement(dieElement, fromRect, {
      originalSide: animation.originalSide,
      rotationDelta: animation.rotationDelta,
      animation: animation.animation,
    });
    // in the case the die was move inside the same stock we don't remove it
    if (animation.fromStock && animation.fromStock != this) {
      animation.fromStock.removeDie(die);
    }
    if (!promise) {
      console.warn("Dicetock.moveFromOtherStock didn't return a Promise");
      promise = Promise.resolve(false);
    }
    return promise;
  };
  DiceStock.prototype.moveFromElement = function (
    die,
    dieElement,
    animation,
    settings
  ) {
    var promise;
    this.addDieElementToParent(dieElement, settings);
    if (animation) {
      if (animation.fromStock) {
        promise = this.animationFromElement(
          dieElement,
          animation.fromStock.element.getBoundingClientRect(),
          {
            originalSide: animation.originalSide,
            rotationDelta: animation.rotationDelta,
            animation: animation.animation,
          }
        );
        animation.fromStock.removeDie(die);
      } else if (animation.fromElement) {
        promise = this.animationFromElement(
          dieElement,
          animation.fromElement.getBoundingClientRect(),
          {
            originalSide: animation.originalSide,
            rotationDelta: animation.rotationDelta,
            animation: animation.animation,
          }
        );
      }
    } else {
      promise = Promise.resolve(false);
    }
    if (!promise) {
      console.warn("Dicetock.moveFromElement didn't return a Promise");
      promise = Promise.resolve(false);
    }
    return promise;
  };
  /**
   * Add an array of dice to the stock.
   *
   * @param dice the dice to add
   * @param animation a `DieAnimation` object
   * @param settings a `AddDiceettings` object
   * @param shift if number, the number of milliseconds between each die. if true, chain animations
   */
  DiceStock.prototype.addDice = function (dice, animation, settings, shift) {
    if (shift === void 0) {
      shift = false;
    }
    return __awaiter(this, void 0, void 0, function () {
      var promises, result, others, _loop_2, i, results;
      var _this = this;
      return __generator(this, function (_a) {
        switch (_a.label) {
          case 0:
            if (!this.manager.animationsActive()) {
              shift = false;
            }
            promises = [];
            if (!(shift === true)) return [3 /*break*/, 4];
            if (!dice.length) return [3 /*break*/, 3];
            return [4 /*yield*/, this.addDie(dice[0], animation, settings)];
          case 1:
            result = _a.sent();
            return [
              4 /*yield*/,
              this.addDice(dice.slice(1), animation, settings, shift),
            ];
          case 2:
            others = _a.sent();
            return [2 /*return*/, result || others];
          case 3:
            return [3 /*break*/, 5];
          case 4:
            if (typeof shift === "number") {
              _loop_2 = function (i) {
                setTimeout(function () {
                  return promises.push(
                    _this.addDie(dice[i], animation, settings)
                  );
                }, i * shift);
              };
              for (i = 0; i < dice.length; i++) {
                _loop_2(i);
              }
            } else {
              promises = dice.map(function (die) {
                return _this.addDie(die, animation, settings);
              });
            }
            _a.label = 5;
          case 5:
            return [4 /*yield*/, Promise.all(promises)];
          case 6:
            results = _a.sent();
            return [
              2 /*return*/,
              results.some(function (result) {
                return result;
              }),
            ];
        }
      });
    });
  };
  /**
   * Remove a die from the stock.
   *
   * @param die die die to remove
   */
  DiceStock.prototype.removeDie = function (die) {
    if (this.contains(die) && this.element.contains(this.getDieElement(die))) {
      this.manager.removeDie(die);
    }
    this.dieRemoved(die);
  };
  /**
   * Notify the stock that a die is removed.
   *
   * @param die the die to remove
   */
  DiceStock.prototype.dieRemoved = function (die) {
    var _this = this;
    var index = this.dice.findIndex(function (c) {
      return _this.manager.getId(c) == _this.manager.getId(die);
    });
    if (index !== -1) {
      this.dice.splice(index, 1);
    }
    if (
      this.selectedDice.find(function (c) {
        return _this.manager.getId(c) == _this.manager.getId(die);
      })
    ) {
      this.unselectDie(die);
    }
  };
  /**
   * Remove a set of dice from the stock.
   *
   * @param dice the dice to remove
   */
  DiceStock.prototype.removeDice = function (dice) {
    var _this = this;
    dice.forEach(function (die) {
      return _this.removeDie(die);
    });
  };
  /**
   * Remove all dice from the stock.
   */
  DiceStock.prototype.removeAll = function () {
    var _this = this;
    var dice = this.getDice(); // use a copy of the array as we iterate and modify it at the same time
    dice.forEach(function (die) {
      return _this.removeDie(die);
    });
  };
  /**
   * Set if the stock is selectable, and if yes if it can be multiple.
   * If set to 'none', it will unselect all selected dice.
   *
   * @param selectionMode the selection mode
   * @param selectableDice the selectable dice (all if unset). Calls `setSelectableDice` method
   */
  DiceStock.prototype.setSelectionMode = function (
    selectionMode,
    selectableDice
  ) {
    var _this = this;
    if (selectionMode !== this.selectionMode) {
      this.unselectAll(true);
    }
    this.dice.forEach(function (die) {
      return _this.setSelectableDie(die, selectionMode != "none");
    });
    this.element.classList.toggle(
      "bga-dice_selectable-stock",
      selectionMode != "none"
    );
    this.selectionMode = selectionMode;
    if (selectionMode === "none") {
      this.getDice().forEach(function (die) {
        return _this.removeSelectionClasses(die);
      });
    } else {
      this.setSelectableDice(
        selectableDice !== null && selectableDice !== void 0
          ? selectableDice
          : this.getDice()
      );
    }
  };
  DiceStock.prototype.setSelectableDie = function (die, selectable) {
    if (this.selectionMode === "none") {
      return;
    }
    var element = this.getDieElement(die);
    var selectableDiceClass = this.getSelectableDieClass();
    var unselectableDiceClass = this.getUnselectableDieClass();
    if (selectableDiceClass) {
      element.classList.toggle(selectableDiceClass, selectable);
    }
    if (unselectableDiceClass) {
      element.classList.toggle(unselectableDiceClass, !selectable);
    }
    if (!selectable && this.isSelected(die)) {
      this.unselectDie(die, true);
    }
  };
  /**
   * Set the selectable class for each die.
   *
   * @param selectableDice the selectable dice. If unset, all dice are marked selectable. Default unset.
   */
  DiceStock.prototype.setSelectableDice = function (selectableDice) {
    var _this = this;
    if (this.selectionMode === "none") {
      return;
    }
    var selectableDiceIds = (
      selectableDice !== null && selectableDice !== void 0
        ? selectableDice
        : this.getDice()
    ).map(function (die) {
      return _this.manager.getId(die);
    });
    this.dice.forEach(function (die) {
      return _this.setSelectableDie(
        die,
        selectableDiceIds.includes(_this.manager.getId(die))
      );
    });
  };
  /**
   * Set selected state to a die.
   *
   * @param die the die to select
   */
  DiceStock.prototype.selectDie = function (die, silent) {
    var _this = this;
    var _a;
    if (silent === void 0) {
      silent = false;
    }
    if (this.selectionMode == "none") {
      return;
    }
    var element = this.getDieElement(die);
    var selectableDiceClass = this.getSelectableDieClass();
    if (!element.classList.contains(selectableDiceClass)) {
      return;
    }
    if (this.selectionMode === "single") {
      this.dice
        .filter(function (c) {
          return _this.manager.getId(c) != _this.manager.getId(die);
        })
        .forEach(function (c) {
          return _this.unselectDie(c, true);
        });
    }
    var selectedDiceClass = this.getSelectedDieClass();
    element.classList.add(selectedDiceClass);
    this.selectedDice.push(die);
    if (!silent) {
      (_a = this.onSelectionChange) === null || _a === void 0
        ? void 0
        : _a.call(this, this.selectedDice.slice(), die);
    }
  };
  /**
   * Set unselected state to a die.
   *
   * @param die the die to unselect
   */
  DiceStock.prototype.unselectDie = function (die, silent) {
    var _this = this;
    var _a;
    if (silent === void 0) {
      silent = false;
    }
    var element = this.getDieElement(die);
    var selectedDiceClass = this.getSelectedDieClass();
    element.classList.remove(selectedDiceClass);
    var index = this.selectedDice.findIndex(function (c) {
      return _this.manager.getId(c) == _this.manager.getId(die);
    });
    if (index !== -1) {
      this.selectedDice.splice(index, 1);
    }
    if (!silent) {
      (_a = this.onSelectionChange) === null || _a === void 0
        ? void 0
        : _a.call(this, this.selectedDice.slice(), die);
    }
  };
  /**
   * Select all dice
   */
  DiceStock.prototype.selectAll = function (silent) {
    var _this = this;
    var _a;
    if (silent === void 0) {
      silent = false;
    }
    if (this.selectionMode == "none") {
      return;
    }
    this.dice.forEach(function (c) {
      return _this.selectDie(c, true);
    });
    if (!silent) {
      (_a = this.onSelectionChange) === null || _a === void 0
        ? void 0
        : _a.call(this, this.selectedDice.slice(), null);
    }
  };
  /**
   * Unelect all dice
   */
  DiceStock.prototype.unselectAll = function (silent) {
    var _this = this;
    var _a;
    if (silent === void 0) {
      silent = false;
    }
    var dice = this.getDice(); // use a copy of the array as we iterate and modify it at the same time
    dice.forEach(function (c) {
      return _this.unselectDie(c, true);
    });
    if (!silent) {
      (_a = this.onSelectionChange) === null || _a === void 0
        ? void 0
        : _a.call(this, this.selectedDice.slice(), null);
    }
  };
  DiceStock.prototype.bindClick = function () {
    var _this = this;
    var _a;
    (_a = this.element) === null || _a === void 0
      ? void 0
      : _a.addEventListener("click", function (event) {
          var dieDiv = event.target.closest(".bga-dice_die");
          if (!dieDiv) {
            return;
          }
          var die = _this.dice.find(function (c) {
            return _this.manager.getId(c) == dieDiv.id;
          });
          if (!die) {
            return;
          }
          _this.dieClick(die);
        });
  };
  DiceStock.prototype.dieClick = function (die) {
    var _this = this;
    var _a;
    if (this.selectionMode != "none") {
      var alreadySelected = this.selectedDice.some(function (c) {
        return _this.manager.getId(c) == _this.manager.getId(die);
      });
      if (alreadySelected) {
        this.unselectDie(die);
      } else {
        this.selectDie(die);
      }
    }
    (_a = this.onDieClick) === null || _a === void 0
      ? void 0
      : _a.call(this, die);
  };
  /**
   * @param element The element to animate. The element is added to the destination stock before the animation starts.
   * @param fromElement The HTMLElement to animate from.
   */
  DiceStock.prototype.animationFromElement = function (
    element,
    fromRect,
    settings
  ) {
    var _a;
    return __awaiter(this, void 0, void 0, function () {
      var side, diceides_1, animation, result;
      return __generator(this, function (_b) {
        switch (_b.label) {
          case 0:
            side = element.dataset.side;
            if (settings.originalSide && settings.originalSide != side) {
              diceides_1 = element.getElementsByClassName("die-sides")[0];
              diceides_1.style.transition = "none";
              element.dataset.side = settings.originalSide;
              setTimeout(function () {
                diceides_1.style.transition = null;
                element.dataset.side = side;
              });
            }
            animation = settings.animation;
            if (animation) {
              animation.settings.element = element;
              animation.settings.fromRect = fromRect;
            } else {
              animation = new BgaSlideAnimation({
                element: element,
                fromRect: fromRect,
              });
            }
            return [4 /*yield*/, this.manager.animationManager.play(animation)];
          case 1:
            result = _b.sent();
            return [
              2 /*return*/,
              (_a =
                result === null || result === void 0
                  ? void 0
                  : result.played) !== null && _a !== void 0
                ? _a
                : false,
            ];
        }
      });
    });
  };
  /**
   * @returns the perspective for this stock.
   */
  DiceStock.prototype.getPerspective = function () {
    var _a, _b;
    return ((_a = this.settings) === null || _a === void 0
      ? void 0
      : _a.perspective) === undefined
      ? this.manager.getPerspective()
      : (_b = this.settings) === null || _b === void 0
      ? void 0
      : _b.perspective;
  };
  /**
   * @returns the class to apply to selectable dice. Use class from manager is unset.
   */
  DiceStock.prototype.getSelectableDieClass = function () {
    var _a, _b;
    return ((_a = this.settings) === null || _a === void 0
      ? void 0
      : _a.selectableDieClass) === undefined
      ? this.manager.getSelectableDieClass()
      : (_b = this.settings) === null || _b === void 0
      ? void 0
      : _b.selectableDieClass;
  };
  /**
   * @returns the class to apply to selectable dice. Use class from manager is unset.
   */
  DiceStock.prototype.getUnselectableDieClass = function () {
    var _a, _b;
    return ((_a = this.settings) === null || _a === void 0
      ? void 0
      : _a.unselectableDieClass) === undefined
      ? this.manager.getUnselectableDieClass()
      : (_b = this.settings) === null || _b === void 0
      ? void 0
      : _b.unselectableDieClass;
  };
  /**
   * @returns the class to apply to selected dice. Use class from manager is unset.
   */
  DiceStock.prototype.getSelectedDieClass = function () {
    var _a, _b;
    return ((_a = this.settings) === null || _a === void 0
      ? void 0
      : _a.selectedDieClass) === undefined
      ? this.manager.getSelectedDieClass()
      : (_b = this.settings) === null || _b === void 0
      ? void 0
      : _b.selectedDieClass;
  };
  DiceStock.prototype.removeSelectionClasses = function (die) {
    this.removeSelectionClassesFromElement(this.getDieElement(die));
  };
  DiceStock.prototype.removeSelectionClassesFromElement = function (
    dieElement
  ) {
    var selectableDiceClass = this.getSelectableDieClass();
    var unselectableDiceClass = this.getUnselectableDieClass();
    var selectedDiceClass = this.getSelectedDieClass();
    dieElement.classList.remove(
      selectableDiceClass,
      unselectableDiceClass,
      selectedDiceClass
    );
  };
  DiceStock.prototype.addRollEffectToDieElement = function (
    die,
    element,
    effect,
    duration
  ) {
    var _a, _b;
    switch (effect) {
      case "rollIn":
        this.manager.animationManager.play(
          new BgaSlideAnimation({
            element: element,
            duration: duration,
            transitionTimingFunction: "ease-out",
            fromDelta: {
              x: 0,
              y:
                ((_a = this.manager.getDieType(die).size) !== null &&
                _a !== void 0
                  ? _a
                  : 50) * 5,
            },
          })
        );
        break;
      case "rollOutPauseAndBack":
        this.manager.animationManager.play(
          new BgaCumulatedAnimation({
            animations: [
              new BgaSlideToAnimation({
                element: element,
                duration: duration,
                transitionTimingFunction: "ease-out",
                fromDelta: {
                  x: 0,
                  y:
                    ((_b = this.manager.getDieType(die).size) !== null &&
                    _b !== void 0
                      ? _b
                      : 50) * -5,
                },
              }),
              new BgaPauseAnimation({}),
              new BgaSlideToAnimation({
                duration: 250,
                transitionTimingFunction: "ease-out",
                element: element,
                fromDelta: {
                  x: 0,
                  y: 0,
                },
              }),
            ],
          })
        );
        break;
      case "turn":
        this.manager.animationManager.play(
          new BgaPauseAnimation({ duration: duration })
        );
        break;
    }
  };
  DiceStock.prototype.rollDice = function (dice, settings) {
    var _this = this;
    dice.forEach(function (die) {
      return _this.rollDie(die, settings);
    });
  };
  DiceStock.prototype.rollDie = function (die, settings) {
    var _a, _b;
    var div = this.getDieElement(die);
    var faces = div.querySelector(".bga-dice_die-faces");
    faces.style.setProperty("--roll-duration", "0");
    faces.clientWidth;
    faces.dataset.visibleFace = "";
    faces.clientWidth;
    var rollEffect =
      (_a =
        settings === null || settings === void 0 ? void 0 : settings.effect) !==
        null && _a !== void 0
        ? _a
        : "rollIn";
    var animate =
      this.manager.animationManager.animationsActive() && rollEffect !== "none";
    var duration =
      (_b =
        settings === null || settings === void 0
          ? void 0
          : settings.duration) !== null && _b !== void 0
        ? _b
        : 1000;
    if (animate) {
      if (Array.isArray(duration)) {
        var diff = Math.abs(duration[1] - duration[0]);
        duration =
          Math.min.apply(Math, duration) + Math.floor(Math.random() * diff);
      }
      if (rollEffect.includes("roll")) {
        faces.style.transform = "rotate3d("
          .concat(Math.random() < 0.5 ? -1 : 1, ", ")
          .concat(Math.random() < 0.5 ? -1 : 1, ", ")
          .concat(Math.random() < 0.5 ? -1 : 1, ", ")
          .concat(720 + Math.random() * 360, "deg)");
        faces.clientWidth;
      }
      this.addRollEffectToDieElement(die, div, rollEffect, duration);
    }
    faces.style.setProperty(
      "--roll-duration",
      "".concat(animate ? duration : 0, "ms")
    );
    faces.clientWidth;
    faces.style.removeProperty("transform");
    faces.dataset.visibleFace = "".concat(die.face);
  };
  return DiceStock;
})();
/**
 * A basic stock for a list of dice, based on flex.
 */
var LineDiceStock = /** @class */ (function (_super) {
  __extends(LineDiceStock, _super);
  /**
   * @param manager the die manager
   * @param element the stock element (should be an empty HTML Element)
   * @param settings a `LineStockSettings` object
   */
  function LineDiceStock(manager, element, settings) {
    var _this = this;
    var _a, _b, _c, _d;
    _this = _super.call(this, manager, element, settings) || this;
    _this.manager = manager;
    _this.element = element;
    element.classList.add("bga-dice_line-stock");
    element.dataset.center = (
      (_a =
        settings === null || settings === void 0 ? void 0 : settings.center) !==
        null && _a !== void 0
        ? _a
        : true
    ).toString();
    element.style.setProperty(
      "--wrap",
      (_b =
        settings === null || settings === void 0 ? void 0 : settings.wrap) !==
        null && _b !== void 0
        ? _b
        : "wrap"
    );
    element.style.setProperty(
      "--direction",
      (_c =
        settings === null || settings === void 0
          ? void 0
          : settings.direction) !== null && _c !== void 0
        ? _c
        : "row"
    );
    element.style.setProperty(
      "--gap",
      (_d =
        settings === null || settings === void 0 ? void 0 : settings.gap) !==
        null && _d !== void 0
        ? _d
        : "8px"
    );
    return _this;
  }
  return LineDiceStock;
})(DiceStock);
/**
 * A stock with fixed slots (some can be empty)
 */
var SlotDiceStock = /** @class */ (function (_super) {
  __extends(SlotDiceStock, _super);
  /**
   * @param manager the die manager
   * @param element the stock element (should be an empty HTML Element)
   * @param settings a `SlotStockSettings` object
   */
  function SlotDiceStock(manager, element, settings) {
    var _this = this;
    var _a, _b;
    _this = _super.call(this, manager, element, settings) || this;
    _this.manager = manager;
    _this.element = element;
    _this.slotsIds = [];
    _this.slots = [];
    element.classList.add("bga-dice_slot-stock");
    _this.mapDieToSlot = settings.mapDieToSlot;
    _this.slotsIds =
      (_a = settings.slotsIds) !== null && _a !== void 0 ? _a : [];
    _this.slotClasses =
      (_b = settings.slotClasses) !== null && _b !== void 0 ? _b : [];
    _this.slotsIds.forEach(function (slotId) {
      _this.createSlot(slotId);
    });
    return _this;
  }
  SlotDiceStock.prototype.createSlot = function (slotId) {
    var _a;
    this.slots[slotId] = document.createElement("div");
    this.slots[slotId].dataset.slotId = slotId;
    this.element.appendChild(this.slots[slotId]);
    (_a = this.slots[slotId].classList).add.apply(
      _a,
      __spreadArray(["slot"], this.slotClasses, true)
    );
  };
  /**
   * Add a die to the stock.
   *
   * @param die the die to add
   * @param animation a `DieAnimation` object
   * @param settings a `AddDieToSlotSettings` object
   * @returns the promise when the animation is done (true if it was animated, false if it wasn't)
   */
  SlotDiceStock.prototype.addDie = function (die, animation, settings) {
    var _a, _b;
    var slotId =
      (_a =
        settings === null || settings === void 0 ? void 0 : settings.slot) !==
        null && _a !== void 0
        ? _a
        : (_b = this.mapDieToSlot) === null || _b === void 0
        ? void 0
        : _b.call(this, die);
    if (slotId === undefined) {
      throw new Error(
        "Impossible to add die to slot : no SlotId. Add slotId to settings or set mapDieToSlot to SlotDie constructor."
      );
    }
    if (!this.slots[slotId]) {
      throw new Error(
        'Impossible to add die to slot "'
          .concat(slotId, '" : slot "')
          .concat(slotId, "\" doesn't exists.")
      );
    }
    var newSettings = __assign(__assign({}, settings), {
      forceToElement: this.slots[slotId],
    });
    return _super.prototype.addDie.call(this, die, animation, newSettings);
  };
  /**
   * Change the slots ids. Will empty the stock before re-creating the slots.
   *
   * @param slotsIds the new slotsIds. Will replace the old ones.
   */
  SlotDiceStock.prototype.setSlotsIds = function (slotsIds) {
    var _this = this;
    if (
      slotsIds.length == this.slotsIds.length &&
      slotsIds.every(function (slotId, index) {
        return _this.slotsIds[index] === slotId;
      })
    ) {
      // no change
      return;
    }
    this.removeAll();
    this.element.innerHTML = "";
    this.slotsIds = slotsIds !== null && slotsIds !== void 0 ? slotsIds : [];
    this.slotsIds.forEach(function (slotId) {
      _this.createSlot(slotId);
    });
  };
  SlotDiceStock.prototype.canAddDie = function (die, settings) {
    var _a, _b;
    if (!this.contains(die)) {
      return true;
    } else {
      var currentDicelot =
        this.getDieElement(die).closest(".slot").dataset.slotId;
      var slotId =
        (_a =
          settings === null || settings === void 0 ? void 0 : settings.slot) !==
          null && _a !== void 0
          ? _a
          : (_b = this.mapDieToSlot) === null || _b === void 0
          ? void 0
          : _b.call(this, die);
      return currentDicelot != slotId;
    }
  };
  /**
   * Swap dice inside the slot stock.
   *
   * @param dice the dice to swap
   * @param settings for `updateInformations` and `selectable`
   */
  SlotDiceStock.prototype.swapDice = function (dice, settings) {
    var _this = this;
    if (!this.mapDieToSlot) {
      throw new Error(
        "You need to define SlotStock.mapDieToSlot to use SlotStock.swapDice"
      );
    }
    var promises = [];
    var elements = dice.map(function (die) {
      return _this.manager.getDieElement(die);
    });
    var elementsRects = elements.map(function (element) {
      return element.getBoundingClientRect();
    });
    var cssPositions = elements.map(function (element) {
      return element.style.position;
    });
    // we set to absolute so it doesn't mess with slide coordinates when 2 div are at the same place
    elements.forEach(function (element) {
      return (element.style.position = "absolute");
    });
    dice.forEach(function (die, index) {
      var _a, _b;
      var dieElement = elements[index];
      var promise;
      var slotId =
        (_a = _this.mapDieToSlot) === null || _a === void 0
          ? void 0
          : _a.call(_this, die);
      _this.slots[slotId].appendChild(dieElement);
      dieElement.style.position = cssPositions[index];
      var dieIndex = _this.dice.findIndex(function (c) {
        return _this.manager.getId(c) == _this.manager.getId(die);
      });
      if (dieIndex !== -1) {
        _this.dice.splice(dieIndex, 1, die);
      }
      if (
        (_b =
          settings === null || settings === void 0
            ? void 0
            : settings.updateInformations) !== null && _b !== void 0
          ? _b
          : true
      ) {
        // after splice/push
        _this.manager.updateDieInformations(die);
      }
      _this.removeSelectionClassesFromElement(dieElement);
      promise = _this.animationFromElement(
        dieElement,
        elementsRects[index],
        {}
      );
      if (!promise) {
        console.warn("Dicetock.animationFromElement didn't return a Promise");
        promise = Promise.resolve(false);
      }
      promise.then(function () {
        var _a;
        return _this.setSelectableDie(
          die,
          (_a =
            settings === null || settings === void 0
              ? void 0
              : settings.selectable) !== null && _a !== void 0
            ? _a
            : true
        );
      });
      promises.push(promise);
    });
    return Promise.all(promises);
  };
  return SlotDiceStock;
})(LineDiceStock);
/**
 * A stock with manually placed dice
 */
var ManualPositionDiceStock = /** @class */ (function (_super) {
  __extends(ManualPositionDiceStock, _super);
  /**
   * @param manager the die manager
   * @param element the stock element (should be an empty HTML Element)
   */
  function ManualPositionDiceStock(manager, element, settings, updateDisplay) {
    var _this = _super.call(this, manager, element, settings) || this;
    _this.manager = manager;
    _this.element = element;
    _this.updateDisplay = updateDisplay;
    element.classList.add("bga-dice_manual-position-stock");
    return _this;
  }
  /**
   * Add a die to the stock.
   *
   * @param die the die to add
   * @param animation a `DieAnimation` object
   * @param settings a `AddDiceettings` object
   * @returns the promise when the animation is done (true if it was animated, false if it wasn't)
   */
  ManualPositionDiceStock.prototype.addDie = function (
    die,
    animation,
    settings
  ) {
    var promise = _super.prototype.addDie.call(this, die, animation, settings);
    this.updateDisplay(this.element, this.getDice(), die, this);
    return promise;
  };
  ManualPositionDiceStock.prototype.dieRemoved = function (die) {
    _super.prototype.dieRemoved.call(this, die);
    this.updateDisplay(this.element, this.getDice(), die, this);
  };
  return ManualPositionDiceStock;
})(DiceStock);
/**
 * A stock to make dice disappear (to automatically remove disdieed dice, or to represent a bag)
 */
var VoidDiceStock = /** @class */ (function (_super) {
  __extends(VoidDiceStock, _super);
  /**
   * @param manager the die manager
   * @param element the stock element (should be an empty HTML Element)
   */
  function VoidDiceStock(manager, element) {
    var _this = _super.call(this, manager, element) || this;
    _this.manager = manager;
    _this.element = element;
    element.classList.add("bga-dice_void-stock");
    return _this;
  }
  /**
   * Add a die to the stock.
   *
   * @param die the die to add
   * @param animation a `DieAnimation` object
   * @param settings a `AddDieToVoidStockSettings` object
   * @returns the promise when the animation is done (true if it was animated, false if it wasn't)
   */
  VoidDiceStock.prototype.addDie = function (die, animation, settings) {
    var _this = this;
    var _a;
    var promise = _super.prototype.addDie.call(this, die, animation, settings);
    // center the element
    var dieElement = this.getDieElement(die);
    var originalLeft = dieElement.style.left;
    var originalTop = dieElement.style.top;
    dieElement.style.left = "".concat(
      (this.element.clientWidth - dieElement.clientWidth) / 2,
      "px"
    );
    dieElement.style.top = "".concat(
      (this.element.clientHeight - dieElement.clientHeight) / 2,
      "px"
    );
    if (!promise) {
      console.warn("VoidStock.addDie didn't return a Promise");
      promise = Promise.resolve(false);
    }
    if (
      (_a =
        settings === null || settings === void 0 ? void 0 : settings.remove) !==
        null && _a !== void 0
        ? _a
        : true
    ) {
      return promise.then(function (result) {
        _this.removeDie(die);
        return result;
      });
    } else {
      dieElement.style.left = originalLeft;
      dieElement.style.top = originalTop;
      return promise;
    }
  };
  return VoidDiceStock;
})(DiceStock);
var DiceManager = /** @class */ (function () {
  /**
   * @param game the BGA game class, usually it will be `this`
   * @param settings: a `DieManagerSettings` object
   */
  function DiceManager(game, settings) {
    var _this = this;
    var _a;
    this.game = game;
    this.settings = settings;
    this.stocks = [];
    this.registeredDieTypes = [];
    this.animationManager =
      (_a = settings.animationManager) !== null && _a !== void 0
        ? _a
        : new AnimationManager(game);
    if (settings.dieTypes) {
      Object.entries(settings.dieTypes).forEach(function (entry) {
        return _this.setDieType(entry[0], entry[1]);
      });
    }
  }
  /**
   * Returns if the animations are active. Animation aren't active when the window is not visible (`document.visibilityState === 'hidden'`), or `game.instantaneousMode` is true.
   *
   * @returns if the animations are active.
   */
  DiceManager.prototype.animationsActive = function () {
    return this.animationManager.animationsActive();
  };
  DiceManager.prototype.addStock = function (stock) {
    this.stocks.push(stock);
  };
  DiceManager.prototype.setDieType = function (type, dieType) {
    this.registeredDieTypes[type] = dieType;
  };
  DiceManager.prototype.getDieType = function (die) {
    return this.registeredDieTypes[die.type];
  };
  DiceManager.prototype.getId = function (die) {
    return "bga-die-".concat(die.type, "-").concat(die.id);
  };
  DiceManager.prototype.createDieElement = function (die) {
    var _a, _b, _c;
    var id = this.getId(die);
    if (this.getDieElement(die)) {
      throw new Error("This die already exists ".concat(JSON.stringify(die)));
    }
    var dieType = this.registeredDieTypes[die.type];
    if (!dieType) {
      throw new Error("This die type doesn't exists ".concat(die.type));
    }
    var element = document.createElement("div");
    element.id = id;
    element.classList.add("bga-dice_die");
    element.style.setProperty(
      "--size",
      "".concat((_a = dieType.size) !== null && _a !== void 0 ? _a : 50, "px")
    );
    var dieFaces = document.createElement("div");
    dieFaces.classList.add("bga-dice_die-faces");
    dieFaces.dataset.visibleFace = "" + die.face;
    element.appendChild(dieFaces);
    var facesElements = [];
    for (var i = 1; i <= dieType.facesCount; i++) {
      facesElements[i] = document.createElement("div");
      facesElements[i].id = "".concat(id, "-face-").concat(i);
      facesElements[i].classList.add("bga-dice_die-face");
      facesElements[i].dataset.face = "" + i;
      dieFaces.appendChild(facesElements[i]);
      element.dataset.face = "" + i;
    }
    document.body.appendChild(element);
    (_b = dieType.setupDieDiv) === null || _b === void 0
      ? void 0
      : _b.call(dieType, die, element);
    if (dieType.setupFaceDiv) {
      for (var i = 1; i <= dieType.facesCount; i++) {
        (_c = dieType.setupFaceDiv) === null || _c === void 0
          ? void 0
          : _c.call(dieType, die, facesElements[i], i);
      }
    }
    document.body.removeChild(element);
    return element;
  };
  /**
   * @param die the die informations
   * @return the HTML element of an existing die
   */
  DiceManager.prototype.getDieElement = function (die) {
    return document.getElementById(this.getId(die));
  };
  /**
   * Remove a die.
   *
   * @param die the die to remove
   */
  DiceManager.prototype.removeDie = function (die) {
    var _a;
    var id = this.getId(die);
    var div = document.getElementById(id);
    if (!div) {
      return false;
    }
    div.id = "deleted".concat(id);
    div.remove();
    // if the die is in a stock, notify the stock about removal
    (_a = this.getDieStock(die)) === null || _a === void 0
      ? void 0
      : _a.dieRemoved(die);
    return true;
  };
  /**
   * Returns the stock containing the die.
   *
   * @param die the die informations
   * @return the stock containing the die
   */
  DiceManager.prototype.getDieStock = function (die) {
    return this.stocks.find(function (stock) {
      return stock.contains(die);
    });
  };
  /**
   * Update the die informations. Used when a change visible face.
   *
   * @param die the die informations
   */
  DiceManager.prototype.updateDieInformations = function (die, updateData) {
    var _this = this;
    var div = this.getDieElement(die);
    div.dataset.visibleFace = "" + die.face;
    if (updateData !== null && updateData !== void 0 ? updateData : true) {
      // die data has changed
      var stock = this.getDieStock(die);
      var dice = stock.getDice();
      var dieIndex = dice.findIndex(function (c) {
        return _this.getId(c) === _this.getId(die);
      });
      if (dieIndex !== -1) {
        stock.dice.splice(dieIndex, 1, die);
      }
    }
  };
  /**
   * @returns the default perspective for all stocks.
   */
  DiceManager.prototype.getPerspective = function () {
    var _a, _b;
    return ((_a = this.settings) === null || _a === void 0
      ? void 0
      : _a.perspective) === undefined
      ? 1000
      : (_b = this.settings) === null || _b === void 0
      ? void 0
      : _b.perspective;
  };
  /**
   * @returns the class to apply to selectable dice. Default 'bga-dice_selectable-die'.
   */
  DiceManager.prototype.getSelectableDieClass = function () {
    var _a, _b;
    return ((_a = this.settings) === null || _a === void 0
      ? void 0
      : _a.selectableDieClass) === undefined
      ? "bga-dice_selectable-die"
      : (_b = this.settings) === null || _b === void 0
      ? void 0
      : _b.selectableDieClass;
  };
  /**
   * @returns the class to apply to selectable dice. Default 'bga-dice_disabled-die'.
   */
  DiceManager.prototype.getUnselectableDieClass = function () {
    var _a, _b;
    return ((_a = this.settings) === null || _a === void 0
      ? void 0
      : _a.unselectableDieClass) === undefined
      ? "bga-dice_disabled-die"
      : (_b = this.settings) === null || _b === void 0
      ? void 0
      : _b.unselectableDieClass;
  };
  /**
   * @returns the class to apply to selected dice. Default 'bga-dice_selected-die'.
   */
  DiceManager.prototype.getSelectedDieClass = function () {
    var _a, _b;
    return ((_a = this.settings) === null || _a === void 0
      ? void 0
      : _a.selectedDieClass) === undefined
      ? "bga-dice_selected-die"
      : (_b = this.settings) === null || _b === void 0
      ? void 0
      : _b.selectedDieClass;
  };
  return DiceManager;
})();
define({
  DiceManager: DiceManager,
  DiceStock: DiceStock,
  LineDiceStock: LineDiceStock,
  SlotDiceStock: SlotDiceStock,
  ManualPositionDiceStock: ManualPositionDiceStock,
  VoidDiceStock: VoidDiceStock,
  sortFunction: sortFunction,
});

/* CUSTOM CODE */

class Die extends BgaDie6 {
  constructor() {
    super();
  }

  setupDieDiv(die, element) {
    super.setupDieDiv(die, element);
    element.classList.add("goi_die");
    element.style.backgroundColor = `#${die.color}`;
  }

  setupFaceDiv(die, element, face) {
    for (let dot = 1; dot <= face; dot++) {
      element.innerHTML += `<div class="goi_dot"></div>`;
    }
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

    element.querySelectorAll(".goi_dot").forEach((dotElement) => {
      dotElement.style.backgroundColor = "yellow";
    });
  }
}
