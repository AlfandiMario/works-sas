import jurnalumum from "./modules/jurnalumum.js";
import terimaBarang from "./modules/terimaBarang.js";
import system from "../../../apps/modules/system/system.js";
import jurnalgaji from "./modules/jurnalgaji.js";
import pengeluaranbarang from "./modules/pengeluaranbarang.js";
import jpbreg from "./modules/jpbreg.js";

export const store = new Vuex.Store({
  modules: {
    jurnalumum: jurnalumum,
    jurnalgaji: jurnalgaji,
    terimaBarang: terimaBarang,
    pengeluaranbarang: pengeluaranbarang,
    jpbreg: jpbreg,
    system: system
  },
  state: {},
  mutations: {},
  actions: {},
});
