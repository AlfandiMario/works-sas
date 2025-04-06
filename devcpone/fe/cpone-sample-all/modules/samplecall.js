// 1 => LOADING
// 2 => DONE
// 3 => ERROR
import * as api from "../api/samplecall.js";

export default {
  namespaced: true,
  state: {
    locations: [],
    selected_location: {},
    last_id: -1,
    last_saved_id: -1,
    x_addr_id: 0,
    act: "edit",
    act_addr: "new",
    get_data_status: 0,
    search_patient: 0,
    search_status: 0,
    search_error_message: "",
    preffix: "",
    patient_name: "",
    suffix: "",
    patients: [],
    selected_patient: {},
    sampletypes: [],
    selected_sampletype: {},
    companies: [{ id: 0, name: "Semua" }],
    selected_company: { id: 0, name: "Semua" },
    total_patients: 0,
    patient_address: [],
    stations: [],
    selected_station: {},
    statuses: [],
    selected_status: {},
    transaction_date: moment(new Date()).format("DD-MM-YYYY"),
    transaction_time: moment(new Date()).format("hh:mm"),
    phone: "",
    hp: "",
    email: "",
    pj: "N",
    dpj: "N",
    clinic: "N",
    is_default: "N",
    marketing_confirm: "N",
    save_status: 0,
    btn_save_seen: true,
    pgrs_save: false,
    save_error_message: "",
    no_save: 0,
    open_alert_confirmation: false,
    alert_success: false,
    msg_success: "",
    dialog_success: false,
    dialog_confirmation_delete: false,
    msg_confirmation_delete: "",
    dialog_confirmation_delete_addr: false,
    msg_confirmation_delete_addr: "",
    autocomplete_status: 0,
    dialog_form_address: false,
    label_address: "",
    addresses: [],
    cities: [],
    city_address: {},
    districts: [],
    district_address: {},
    kelurahans: [],
    kelurahan_address: {},
    description_address: "",
    errors: [],
    dialog_action: false,
    msg_action: "",
    current_page: 1,
    total_page: 1,
    dialog_requirement: false,
    requirements: [],
    selected_sample: {},
    open_dialog_info: false,
    msg_info: "",
    name: "",
    nolab: "",
    information_bahan: [],
    staff: { id: 0, code: "", name: "BELUM ADA STAF", code: "", userid: "" },
    dialog_note: false,
    msg_note: "",
    note_requirement: [],
    dialog_form_note: "",
    barcode_search_tube: false,
    barcode_search: false,
    barcode_search_string: "",
    start_date: moment(new Date()).format("YYYY-MM-DD"),
  },
  mutations: {
    update_start_date(state, val) {
      state.start_date = val;
    },
    update_barcode_search_string(state, val) {
      state.barcode_search_string = val;
    },
    update_barcode_search(state, val) {
      state.barcode_search = val;
    },
    update_barcode_search_tube(state, val) {
      state.barcode_search_tube = val;
    },
    update_dialog_form_note(state, val) {
      state.dialog_form_note = val;
    },
    update_note_requirement(state, val) {
      state.note_requirement = val;
    },
    update_dialog_note(state, val) {
      state.dialog_note = val;
    },
    update_msg_note(state, val) {
      state.msg_note = val;
    },
    update_companies(state, val) {
      state.companies = val;
    },
    update_selected_company(state, val) {
      state.selected_company = val;
    },
    update_staff(state, val) {
      state.staff = val;
    },
    update_information_bahan(state, val) {
      state.information_bahan = val;
    },
    update_name(state, val) {
      state.name = val;
    },
    update_nolab(state, val) {
      state.nolab = val;
    },
    update_open_dialog_info(state, val) {
      state.open_dialog_info = val;
    },
    update_msg_info(state, val) {
      state.msg_info = val;
    },
    update_selected_sample(state, val) {
      state.selected_sample = val;
    },
    update_requirements(state, val) {
      state.requirements = val;
    },
    update_dialog_requirement(state, val) {
      state.dialog_requirement = val;
    },
    update_total_page(state, val) {
      state.total_page = val;
    },
    update_current_page(state, val) {
      state.current_page = val;
    },
    update_x_addr_id(state, val) {
      state.x_addr_id = val;
    },
    update_last_id(state, val) {
      state.last_id = val;
    },
    update_last_saved_id(state, val) {
      state.last_saved_id = val;
    },
    update_act(state, val) {
      state.act = val;
    },
    update_act_addr(state, val) {
      state.act_addr = val;
    },
    update_get_data_status(state, val) {
      state.get_data_status = val;
    },
    update_search_error_message(state, patient) {
      state.search_error_message = patient;
    },
    update_search_patient(state, patient) {
      state.search_patient = patient;
    },
    update_preffix(state, val) {
      state.preffix = val;
    },
    update_patient_name(state, val) {
      state.patient_name = val;
    },
    update_suffix(state, val) {
      state.suffix = val;
    },
    update_patients(state, data) {
      state.patients = data;
    },
    update_selected_patient(state, val) {
      state.selected_patient = val;
    },
    update_sampletypes(state, data) {
      state.sampletypes = data;
    },
    update_selected_sampletype(state, val) {
      state.selected_sampletype = val;
    },
    update_stations(state, val) {
      state.stations = val;
    },
    update_selected_station(state, val) {
      state.selected_station = val;
    },
    update_statuses(state, val) {
      state.statuses = val;
    },
    update_selected_status(state, val) {
      state.selected_status = val;
    },
    update_phone(state, val) {
      state.phone = val;
    },
    update_email(state, val) {
      state.email = val;
    },
    update_hp(state, val) {
      state.hp = val;
    },
    update_pj(state, val) {
      state.pj = val;
    },
    update_dpj(state, val) {
      state.dpj = val;
    },
    update_clinic(state, val) {
      state.clinic = val;
    },
    update_marketing_confirm(state, val) {
      state.marketing_confirm = val;
    },
    update_is_default(state, val) {
      state.is_default = val;
    },
    update_save_status(state, val) {
      state.save_status = val;
    },
    update_btn_save_seen(state, val) {
      state.btn_save_seen = val;
    },
    update_pgrs_save(state, val) {
      state.pgrs_save = val;
    },
    update_save_error_message(state, msg) {
      state.save_error_message = "";
    },
    update_no_save(state, val) {
      state.no_save = val;
    },
    update_open_alert_confirmation(state, val) {
      state.open_alert_confirmation = val;
    },
    update_alert_success(state, val) {
      state.alert_success = val;
    },
    update_msg_success(state, val) {
      state.msg_success = val;
    },
    update_dialog_success(state, val) {
      state.dialog_success = val;
    },
    update_dialog_confirmation_delete(state, val) {
      state.dialog_confirmation_delete = val;
    },
    update_msg_confirmation_delete(state, val) {
      state.msg_confirmation_delete = val;
    },
    update_dialog_confirmation_delete_addr(state, val) {
      state.dialog_confirmation_delete_addr = val;
    },
    update_msg_confirmation_delete_addr(state, val) {
      state.msg_confirmation_delete_addr = val;
    },
    update_addresses(state, val) {
      state.addresses = val;
    },
    update_autocomplete_status(state, val) {
      state.autocomplete_status = val;
    },
    update_dialog_form_address(state, val) {
      state.dialog_form_address = val;
    },
    update_label_address(state, val) {
      state.label_address = val;
    },
    update_cities(state, val) {
      state.cities = val;
    },
    update_city_address(state, val) {
      state.city_address = val;
    },
    update_districts(state, val) {
      state.districts = val;
    },
    update_district_address(state, val) {
      state.district_address = val;
    },
    update_kelurahans(state, val) {
      state.kelurahans = val;
    },
    update_kelurahan_address(state, val) {
      state.kelurahan_address = val;
    },
    update_description_address(state, val) {
      state.description_address = val;
    },
    update_search_status(state, val) {
      state.search_status = val;
    },
    update_errors(state, val) {
      state.errors = val;
    },
    update_total_patients(state, val) {
      state.total_patients = val;
    },
    update_dialog_action(state, val) {
      state.dialog_action = val;
    },
    update_msg_action(state, val) {
      state.msg_action = val;
    },
    update_locations(state, val) {
      state.locations = val.records;
    },
    update_selected_location(state, val) {
      state.selected_location = val;
    },
  },
  actions: {
    async search(context, prm) {
      context.commit("update_search_patient", 1);
      window.key_enter = "";
      try {
        console.log(prm);
        console.log("search");
        prm.token = one_token();
        console.log(prm);
        let resp = await api.search(prm);
        if (resp.status != "OK") {
          context.commit("update_search_patient", 3);
          context.commit("update_search_error_message", resp.message);
        } else {
          context.commit("update_search_patient", 2);
          context.commit("update_search_error_message", "");
          let data = {
            records: resp.data.records,
            total: resp.data.total,
          };
          context.commit("update_patients", data.records);
          context.commit("update_total_patients", data.total);
          //context.commit("update_total_page", data.total)
          context.commit("update_no_save", 0);
          //context.commit("update_barcode_search",false)
          //context.commit("update_barcode_search_tube",false)
          if (prm.lastid === -1) {
            var pat = data.records[0];
            if (data.records.length === 0) {
              context.commit("update_selected_patient", {});
              context.commit("update_sampletypes", {});
            } else {
              context.commit("update_selected_patient", data.records[0]);
              context.dispatch("getsampletypes", {
                orderid: pat.T_OrderHeaderID,
                stationid: pat.T_SampleStationID,
                statusid: pat.statusid,
              });
            }
          } else {
            console.log("oyyeee");
            context.commit("update_last_id", prm.lastid);
            context.commit("update_selected_patient", data.records[prm.lastid]);
            var pat = data.records[prm.lastid];
            context.dispatch("getsampletypes", {
              orderid: pat.T_OrderHeaderID,
              stationid: pat.T_SampleStationID,
              statusid: pat.statusid,
            });
          }
        }
      } catch (e) {
        context.commit("update_search_patient", 3);
        context.commit("update_search_error_message", e.message);
        console.log(e);
      }
    },
    async getstationstatus(context, prm) {
      context.commit("update_get_data_status", 1);
      try {
        let resp = await api.getstationstatus(one_token());
        if (resp.status != "OK") {
          context.commit("update_get_data_status", 3);
        } else {
          context.commit("update_get_data_status", 2);
          let data = {
            records: resp.data.records,
            total: resp.data.total,
          };
          context.commit("update_stations", data.records.stations);
          context.commit("update_selected_station", data.records.stations[0]);
          context.commit("update_statuses", data.records.statuses);
          context.commit("update_selected_status", data.records.statuses[0]);
          await context.dispatch("getLocation", data.records.stations[0].id);
          prm.stationid = data.records.stations[0].id;
          prm.statusid = data.records.statuses[0].id;
          prm.locationid = context.state.selected_location.locationID;
          console.log(prm);
          context.dispatch("search", prm);
        }
      } catch (e) {
        console.log(e);
        context.commit("update_get_data_status", 3);
      }
    },
    async getLocation(context, prm) {
      context.commit("update_get_data_status", 1);
      try {
        let param = { token: one_token(), station_id: prm };
        let resp = await api.getLocation(param);
        if (resp.status != "OK") {
          context.commit("update_get_data_status", 3);
        } else {
          context.commit("update_get_data_status", 2);
          let data = {
            records: resp.data.records,
            total: resp.data.total,
          };
          context.commit("update_locations", resp.data);
          context.commit("update_selected_location", resp.data.records[0]);
          console.log(data);
        }
      } catch (e) {
        console.log(e);
        context.commit("update_get_data_status", 3);
      }
    },
    async savenotesampling(context, prm) {
      context.commit("update_get_data_status", 1);
      try {
        prm.token = one_token();
        let resp = await api.savenotesampling(prm);
        if (resp.status != "OK") {
          context.commit("update_get_data_status", 3);
        } else {
          context.commit("update_get_data_status", 2);
          let data = {
            records: resp.data.records,
            total: resp.data.total,
          };
          context.commit("update_dialog_form_note", false);
          console.log(prm);
          context.dispatch("search", prm.search);
        }
      } catch (e) {
        context.commit("update_get_data_status", 3);
      }
    },
    async getrequirements(context, prm) {
      context.commit("update_get_data_status", 1);
      try {
        prm.token = one_token();
        let resp = await api.getrequirements(prm);
        if (resp.status != "OK") {
          context.commit("update_get_data_status", 3);
        } else {
          context.commit("update_get_data_status", 2);
          let data = {
            records: resp.data.records,
            total: resp.data.total,
          };
          context.commit("update_requirements", data.records);
          context.commit("update_dialog_requirement", true);
        }
      } catch (e) {
        context.commit("update_get_data_status", 3);
      }
    },
    async getdatanoterequirement(context, prm) {
      context.commit("update_get_data_status", 1);
      try {
        prm.token = one_token();
        let resp = await api.getdatanoterequirement(prm);
        if (resp.status != "OK") {
          context.commit("update_get_data_status", 3);
        } else {
          context.commit("update_get_data_status", 2);
          let data = {
            records: resp.data.records,
            total: resp.data.total,
          };
          context.commit("update_note_requirement", data.records);
          context.commit("update_dialog_note", true);
        }
      } catch (e) {
        context.commit("update_get_data_status", 3);
      }
    },
    async save(context, prm) {
      context.commit("update_save_status", 1);
      try {
        prm.token = one_token();
        let resp = await api.save(prm);
        if (resp.status != "OK") {
          context.commit("update_save_status", 3);
        } else {
          context.commit("update_save_status", 2);
          context.commit("update_last_id", prm.M_patientID);
          context.commit("update_dialog_success", true);
          var msg =
            "Data dokter " + prm.M_patientName + " sudah terupdate dong ...";
          context.commit("update_msg_success", msg);
        }
      } catch (e) {
        context.commit("update_save_status", 3);
      }
    },
    async receivesample(context, prm) {
      context.commit("update_save_status", 1);
      try {
        prm.token = one_token();
        let resp = await api.doaction(prm);
        if (resp.status != "OK") {
          context.commit("update_save_status", 3);
        } else {
          context.commit("update_save_status", 2);
          context.commit("update_barcode_search", false);
          context.commit("update_barcode_search_tube", false);
          context.commit("update_barcode_search_string", "");
          let data = {
            records: resp.data.records,
            total: resp.data.total,
          };
          //if(data.records.status === 'OK')
          //prm.search.lastid = -1

          if (data.records["status"] === "PARTIAL") {
            console.log("alooohaa");
            //context.dispatch("search",prm)
            var patients = context.state.patients;
            var idx = _.findIndex(patients, function (o) {
              return o.T_OrderHeaderID == prm.id;
            });
            context.commit("update_last_id", idx);
            var xprm = {
              orderid: patients[idx].T_OrderHeaderID,
              stationid: patients[idx].T_SampleStationID,
              statusid: patients[idx].statusid,
            };
            console.log(xprm);
            context.dispatch("getsampletypes", xprm);
          } else {
            prm.search.lastid = -1;
            context.dispatch("search", prm.search);
          }

          //context.dispatch("search",prm.search)
        }
      } catch (e) {
        context.commit("update_save_status", 3);
      }
    },
    async addnewlabel(context, prm) {
      context.commit("update_save_status", 1);
      try {
        prm.token = one_token();
        let resp = await api.addnewlabel(prm);
        if (resp.status != "OK") {
          context.commit("update_save_status", 3);
        } else {
          context.commit("update_save_status", 2);
          context.commit("update_dialog_action", false);
          /*let data = {
                        records : resp.data.records,
                        total: resp.data.total
                    }
                    if(data.records.status === 'OK')
                        prm.search.lastid = -1*/
          console.log(prm);
          console.log("beofre");
          console.log(prm);

          context.dispatch("search", prm);
        }
      } catch (e) {
        context.commit("update_save_status", 3);
      }
    },
    async saverequirement(context, prm) {
      context.commit("update_save_status", 1);
      try {
        prm.token = one_token();
        let resp = await api.saverequirement(prm);
        if (resp.status != "OK") {
          context.commit("update_save_status", 3);
        } else {
          context.commit("update_save_status", 2);
          context.commit("update_dialog_requirement", false);
        }
      } catch (e) {
        context.commit("update_save_status", 3);
      }
    },
    async newpatient(context, prm) {
      context.commit("update_save_status", 1);
      try {
        prm.token = one_token();
        let resp = await api.newpatient(prm);
        if (resp.status != "OK") {
          context.commit("update_save_status", 3);
        } else {
          context.commit("update_save_status", 2);
          console.log(resp.data.id);
          context.commit("update_last_id", resp.data.id);
          context.commit("update_dialog_success", true);
          var msg =
            "Data dokter " + prm.M_patientName + " sudah tersimpan dong ...";
          context.commit("update_msg_success", msg);
        }
      } catch (e) {
        context.commit("update_save_status", 3);
      }
    },
    async delete(context, prm) {
      context.commit("update_save_status", 1);
      try {
        prm.token = one_token();
        let resp = await api.xdelete(prm);
        if (resp.status != "OK") {
          context.commit("update_save_status", 3);
        } else {
          context.commit("update_save_status", 2);
          context.commit("update_last_id", 0);
          context.commit("update_dialog_confirmation_delete", false);
          context.commit("update_dialog_success", true);
          var msg =
            "Data dokter " + prm.M_patientName + " sudah dihapus dong ...";
          context.commit("update_msg_success", msg);
          context.commit("update_preffix", "");
          context.commit("update_patient_name", "");
          context.commit("update_suffix", "");
          context.commit("update_selected_sex", {});
          context.commit("update_selected_religion", {});
          context.commit("update_phone", "");
          context.commit("update_email", "");
          context.commit("update_hp", "");
          context.commit("update_pj", "N");
          context.commit("update_dpj", "N");
          context.commit("update_clinic", "N");
          context.commit("update_marketing_confirm", "N");
          context.commit("update_is_default", "N");
        }
      } catch (e) {
        context.commit("update_save_status", 3);
      }
    },
    async getaddress(context, prm) {
      context.commit("update_save_status", 1);
      try {
        prm.token = one_token();
        let resp = await api.getaddress(prm);
        if (resp.status != "OK") {
          context.commit("update_save_status", 3);
        } else {
          context.commit("update_save_status", 2);
          let data = {
            records: resp.data.records,
            total: resp.data.total,
          };
          context.commit("update_addresses", data.records);
        }
      } catch (e) {
        context.commit("update_save_status", 3);
      }
    },
    async searchcity(context, prm) {
      context.commit("update_autocomplete_status", 1);
      try {
        let resp = await api.searchcity(one_token(), prm);
        if (resp.status != "OK") {
          context.commit("update_autocomplete_status", 3);
        } else {
          context.commit("update_autocomplete_status", 2);
          let data = {
            records: resp.data.records,
            total: resp.data.total,
          };
          context.commit("update_cities", resp.data.records);
        }
      } catch (e) {
        context.commit("update_autocomplete_status", 3);
      }
    },
    async getdistrict(context, prm) {
      context.commit("update_get_data_status", 1);
      try {
        let resp = await api.getdistrict(one_token(), prm);
        if (resp.status != "OK") {
          context.commit("update_get_data_status", 3);
        } else {
          context.commit("update_get_data_status", 2);
          let data = {
            records: resp.data.records,
            total: resp.data.total,
          };
          context.commit("update_districts", resp.data.records);
        }
      } catch (e) {
        context.commit("update_get_data_status", 3);
      }
    },
    async search_staff(context, prm) {
      context.commit("update_get_data_status", 1);
      try {
        prm.token = one_token();
        let resp = await api.search_staff(prm);
        if (resp.status != "OK") {
          context.commit("update_get_data_status", 3);
        } else {
          context.commit("update_get_data_status", 2);
          let data = {
            records: resp.data.records,
            total: resp.data.total,
          };
          if (data.records) context.commit("update_staff", resp.data.records);
          else {
            context.commit("update_staff", {
              id: 0,
              code: "",
              name: "STAF TIDAK DITEMUKAN",
              code: "",
              userid: "",
            });
          }
        }
      } catch (e) {
        context.commit("update_get_data_status", 3);
      }
    },
    search_patient_enter(context, prm) {
      context.commit("update_get_data_status", 1);
      //console.log(prm)
      //console.log(context.state.nolab)
      window.key_enter = "";
      if (context.state.nolab === "") {
        // console.log(prm)
        context.commit("update_barcode_search", false);
        context.commit("update_barcode_search_tube", false);

        var last_selected = context.state.selected_patient;
        var xsearch = prm.search;

        if (prm.search.length > 10) {
          xsearch = xsearch.substring(0, xsearch.length - 2);
        }
        console.log(xsearch);
        if (last_selected.T_OrderHeaderLabNumber === xsearch) {
          var act = "call";
          var status = 1;
          if (last_selected.status === "Call") {
            act = "process";
            status = 3;
          }
          if (prm.search.length > 10) {
            context.commit("update_barcode_search", true);
            context.commit("update_barcode_search_tube", true);
            context.commit("update_barcode_search_string", prm.search);
          }

          var patients = context.state.patients;
          var patient = context.state.selected_patient;
          var lastid = _.findIndex(patients, function (o) {
            return o.T_OrderHeaderID == patient.T_OrderHeaderID;
          });
          if (last_selected.status !== "Process") {
            context.dispatch("doaction", {
              act: act,
              id: patient.T_OrderHeaderID,
              xdate: context.state.start_date,
              name: context.state.name,
              nolab: context.state.nolab,
              stationid: patient.T_SampleStationID,
              statusid: context.state.selected_status.id,
              statusnextid: status,
              sample: {},
              lastid: lastid,
              companyid: context.state.selected_company.id,
              staff: context.state.staff,
            });
          }
          console.log(context.state.barcode_search_tube);
          console.log(last_selected.status);
          if (
            context.state.barcode_search_tube &&
            last_selected.status === "Process"
          ) {
            var xsearch = context.state.barcode_search_string;
            var sampletypes = context.state.sampletypes;
            console.log("start");
            console.log(context.state.barcode_search_tube);
            var search_minus_one = xsearch.substring(0, xsearch.length);
            //console.log(search_minus_one)
            var samples = _.filter(sampletypes, function (o) {
              return (
                o.T_BarcodeLabBarcode.substring(
                  0,
                  o.T_BarcodeLabBarcode.length - 1
                ) == search_minus_one
              );
            });
            console.log(samples);
            if (samples && samples.length != -1 && samples[0].status === "P") {
              //console.log("masuk")
              samples.forEach((el) => {
                el.requirement_status = "Y";
              });
              _.forEach(samples, function (value, idxx) {
                samples[idxx].requirements.forEach((el) => {
                  el.chex = "N";
                });
              });

              context.commit("update_sampletypes", sampletypes);
              context.commit("update_act", "samplingdone");
              var barcode_prm = patient;
              barcode_prm.id = patient.T_OrderHeaderID;
              barcode_prm.act = "samplingdone";
              barcode_prm.typeaction = "multi";
              barcode_prm.sample = samples;
              barcode_prm.staff = context.state.staff;
              barcode_prm.search = {
                xdate: context.state.start_date,
                name: context.state.name,
                nolab: context.state.nolab,
                stationid: context.state.selected_station.id,
                statusid: context.state.selected_status.id,
                companyid: context.state.selected_company.id,
                lastid: context.state.last_id,
              };
              context.dispatch("receivesample", barcode_prm);
            }
          }
        } else {
          window.key_enter = "";
          var patients = context.state.patients;
          var idx = _.findIndex(patients, function (o) {
            return o.T_OrderHeaderLabNumber === xsearch;
          });
          console.log("idx : " + idx);
          if (idx != -1) {
            context.commit("update_selected_patient", patients[idx]);
            var pat = context.state.selected_patient;
            context.dispatch("getsampletypes", {
              orderid: pat.T_OrderHeaderID,
              stationid: pat.T_SampleStationID,
              statusid: pat.statusid,
            });
          }
        }
      } else {
        var param = {
          xdate: context.state.start_date,
          name: context.state.name,
          nolab: context.state.nolab,
          stationid: context.state.selected_station.id,
          statusid: context.state.selected_status.id,
          companyid: context.state.selected_company.id,
          lastid: -1,
        };
        context.dispatch("search", param);
      }
    },
    search_patientx(context, prm) {
      context.commit("update_get_data_status", 1);
      prm.token = one_token();
    },
    async getkelurahan(context, prm) {
      context.commit("update_get_data_status", 1);
      try {
        let resp = await api.getkelurahan(one_token(), prm);
        if (resp.status != "OK") {
          context.commit("update_get_data_status", 3);
        } else {
          context.commit("update_get_data_status", 2);
          let data = {
            records: resp.data.records,
            total: resp.data.total,
          };
          context.commit("update_kelurahans", resp.data.records);
        }
      } catch (e) {
        context.commit("update_get_data_status", 3);
      }
    },
    async savenewaddress(context, prm) {
      context.commit("update_save_status", 1);
      try {
        prm.token = one_token();
        let resp = await api.savenewaddress(prm);
        if (resp.status != "OK") {
          context.commit("update_save_status", 3);
        } else {
          context.commit("update_save_status", 2);
          context.commit("update_dialog_form_address", false);
          context.commit("update_last_id", prm.M_patientAddressM_patientID);
          context.commit("update_dialog_success", true);
          var msg =
            "Penambahan data alamat dokter " +
            prm.M_patientName +
            " sudah berhasil dong ...";
          context.commit("update_msg_success", msg);
        }
      } catch (e) {
        context.commit("update_save_status", 3);
      }
    },
    async saveeditaddress(context, prm) {
      context.commit("update_save_status", 1);
      try {
        prm.token = one_token();
        let resp = await api.saveeditaddress(prm);
        if (resp.status != "OK") {
          context.commit("update_save_status", 3);
        } else {
          context.commit("update_save_status", 2);
          context.commit("update_dialog_form_address", false);
          context.commit("update_last_id", prm.M_patientAddressM_patientID);
          context.commit("update_dialog_success", true);
          var msg =
            "Perubahan data alamat dokter " +
            prm.M_patientName +
            " sudah berhasil dong ...";
          context.commit("update_msg_success", msg);
        }
      } catch (e) {
        context.commit("update_save_status", 3);
      }
    },
    async deleteaddress(context, prm) {
      context.commit("update_save_status", 1);
      try {
        prm.token = one_token();
        let resp = await api.deleteaddress(prm);
        if (resp.status != "OK") {
          context.commit("update_save_status", 3);
        } else {
          context.commit("update_save_status", 2);
          context.commit("update_dialog_confirmation_delete_addr", false);
          context.commit("update_last_id", prm.M_patientAddressM_patientID);
          context.commit("update_dialog_success", true);
          var msg =
            "Penghapusan data alamat " +
            prm.M_patientAddressNote +
            " dari dokter " +
            prm.M_patientName +
            " sudah berhasil dong ...";
          context.commit("update_msg_success", msg);
        }
      } catch (e) {
        context.commit("update_save_status", 3);
      }
    },

    async getsampletypes(context, prm) {
      context.commit("update_save_status", 1);
      try {
        prm.token = one_token();
        // check if prm.labnumber still empty
        if (prm.labnumber === "") {
          prm.labnumber = context.state.selected_patient.T_OrderHeaderLabNumber;
        }
        if (prm.stationid === "") {
          prm.stationid = context.state.selected_station.id;
        }

        let resp = await api.getsampletypes(prm);
        if (resp.status != "OK") {
          context.commit("update_save_status", 3);
        } else {
          context.commit("update_save_status", 2);
          let data = {
            records: resp.data.records,
            total: resp.data.total,
          };
          context.commit("update_sampletypes", data.records["sampletypes"]);
          context.commit(
            "update_information_bahan",
            data.records["information_bahan"]
          );
          if (context.state.barcode_search) {
            var patients = context.state.patients;
            var selected_patient = context.state.selected_patient;
            var last_id = _.findIndex(patients, function (o) {
              return o.T_OrderHeaderID == selected_patient.T_OrderHeaderID;
            });
            context.commit("update_last_id", last_id);

            if (context.state.barcode_search_tube && selected_patient.status === "Process") {
              var xsearch = context.state.barcode_search_string;
              var sampletypes = context.state.sampletypes;
              console.log("start");
              console.log(context.state.barcode_search_tube);
              var search_minus_one = xsearch.substring(0, xsearch.length);
              //console.log(search_minus_one)
              var samples = _.filter(sampletypes, function (o) {
                return (
                  o.T_BarcodeLabBarcode.substring(
                    0,
                    o.T_BarcodeLabBarcode.length - 1
                  ) == search_minus_one
                );
              });
              //console.log(samples)
              if (
                samples &&
                samples.length != -1 &&
                samples[0].status === "P"
              ) {
                //console.log("masuk")
                samples.forEach((el) => {
                  el.requirement_status = "Y";
                });
                _.forEach(samples, function (value, idxx) {
                  samples[idxx].requirements.forEach((el) => {
                    el.chex = "N";
                  });
                });

                context.commit("update_sampletypes", sampletypes);

                context.commit("update_act", "samplingdone");
                var barcode_prm = selected_patient;
                barcode_prm.id = selected_patient.T_OrderHeaderID;
                barcode_prm.act = "samplingdone";
                barcode_prm.typeaction = "multi";
                barcode_prm.sample = samples;
                barcode_prm.staff = context.state.staff;
                barcode_prm.search = {
                  xdate: context.state.start_date,
                  name: context.state.name,
                  nolab: context.state.nolab,
                  stationid: context.state.selected_station.id,
                  statusid: context.state.selected_status.id,
                  companyid: context.state.selected_company.id,
                  lastid: context.state.last_id,
                };
                context.dispatch("receivesample", barcode_prm);
              } else {
                context.commit(
                  "update_msg_info",
                  "Tetap fokus, yang sudah biarlah sudah"
                );
                context.commit("update_open_dialog_info", true);
              }
            } else {
              context.commit("update_barcode_search", false);
              context.commit("update_barcode_search_tube", false);
              context.commit("update_barcode_search_string", "");
            }
          }
        }
      } catch (e) {
        context.commit("update_save_status", 3);
      }
    },

    async searchcompany(context, prm) {
      context.commit("update_autocomplete_status", 1);
      try {
        let resp = await api.searchcompany(one_token(), prm);
        if (resp.status != "OK") {
          context.commit("update_autocomplete_status", 3);
        } else {
          context.commit("update_autocomplete_status", 2);
          let data = {
            records: resp.data.records,
            total: resp.data.total,
          };
          context.commit("update_companies", resp.data.records);
        }
      } catch (e) {
        context.commit("update_autocomplete_status", 3);
      }
    },
    
    async doaction(context, prm) {
      context.commit("update_save_status", 1);
      try {
        prm.token = one_token();
        let resp = await api.doaction(prm);
        if (resp.status != "OK") {
          context.commit("update_save_status", 3);
        } else {
          context.commit("update_save_status", 2);
          let data = {
            records: resp.data.records,
            total: resp.data.total,
          };
          context.commit("update_act", "-");

          context.commit("update_dialog_action", false);
          if (data.records["status"] === "NOTCALL") {
            var dt = data.records["data"];
            var msg =
              " Bagai pinang dibelah dua, pasiennya lagi dipanggil di " +
              dt.T_SampleStationName;
            context.commit("update_msg_info", msg);
            context.commit("update_open_dialog_info", true);
            context.dispatch("search", prm);
          }
          //prm.lastid = -1
          if (data.records["status"] === "OK") {
            //context.dispatch("search",prm)
            var patients = context.state.patients;
            var idx = _.findIndex(patients, function (o) {
              return o.T_OrderHeaderID == prm.id;
            });
            //console.log(idx)
            if (
              prm.act == "call" ||
              prm.act == "skip" ||
              prm.act == "process"
            ) {
              patients[idx].statusid = prm.statusnextid.toString();
              if (prm.act == "call") patients[idx].status = "Call";
              if (prm.act == "skip") patients[idx].status = "Skip";
              if (prm.act == "process") patients[idx].status = "Process";
              //console.log(patients)
              context.commit("update_patients", patients);
              if (prm.act == "process") {
                context.commit("update_last_id", idx);
                var xprm = {
                  orderid: patients[idx].T_OrderHeaderID,
                  stationid: patients[idx].T_SampleStationID,
                  statusid: patients[idx].statusid,
                };
                console.log(xprm);
                context.dispatch("getsampletypes", xprm);
              }
            }

            if (resp.data.nextstatus === "5" || resp.data.nextstatus === 5 || prm.act == 'skip') {
              context.dispatch("search", prm);
            }
          }
        }
      } catch (e) {
        context.commit("update_save_status", 3);
      }
    },
  },
};
