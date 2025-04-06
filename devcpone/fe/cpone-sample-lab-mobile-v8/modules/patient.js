// 1 => LOADING
// 2 => DONE
// 3 => ERROR
import * as api from "../api/patient.js"

export default {
    namespaced: true,
    state: {
        lastidx: 0,
        lookup_status: 0,
        lookup_error_message: '',
        search_patient: 0,
        search_error_message: '',
        start_date: moment(new Date()).format('YYYY-MM-DD'),
        end_date: moment(new Date()).format('YYYY-MM-DD'),
        search: '',
        total_patiens: 0,
        total_filter_patiens: 0,
        total_patients_all: 0,
        patients: [],
        total_patient: 0,
        selected_patient: { totalbill: 0 },
        save_error_message: '',
        statuses: [],
        selected_status: {},
        open_alert_no_pay: false,
        msg_alert_no_pay: "Loh ... Gak jadi bayar dong ?",
        current_page: 1,
        total_page: 0,
        filters: [{ id: 'day', name: 'Hari ini' }, { id: 'notsampled', name: 'Belum sampling' }, { id: 'all', name: 'Tampilkan semua' }],
        selected_filter: { id: 'day', name: 'Hari ini' },
        promise_dialog: false,
        promises: [],
        errors: [],
        save_status: 0,
        save_error_message: '',
        barcodes: [],
        dialog_barcode: false,
        autocomplete_status: 0,
        companies: [],
        selected_company: { id: 0, name: 'Semua' },
        dialog_details: false,
        order_id: 0,
        noreg: '',
        data_patient: [],
        data_packet: [],
        data_tests: [],
        data_sample_lab: [],
        data_sample_lab_undone: [],
        data_sample_lab_done: [],
        data_sample_radiodiagnostic: [],
        data_sample_electromedic: [],
        data_sample_other: [],
        data_requirement: [],
        dialog_scanner: false,
        selected_patient: {},
        sampletypes: [],
        act_scan: '',
        alert_msg: "",
        alert_status: false,
        isScanning: false,
        html5QrCode: null,
        stations: [],
        selected_station: {},
        locations: [],
        selected_location: {},
        get_data_status: 0,
        isdone: "X",
        url_labnumber: "",
        dialog_done: false,
        status_done: false,
        station_id: -1,
        location_id: -1,
        value_tb: 0,
        value_bb: 0,
        value_bf: 0,
        tkod: '',
        tkos: '',
        dkod: '',
        dkos: '',
        btwrn: 'N',
        withCorection: false,
        visusAdd: '',
        odSph: '',
        odCyl: '',
        odX: '',
        osSph: '',
        osCyl: '',
        osX: '',
        colorBlindNumber: '',
        dialogError: false,
        errorMsg: '',
        urls: {},
        glucoses:[]
    },
    mutations: {
        update_glucoses(state, val) {
            state.glucoses = val
        },
        update_urls(state, val) {
            state.urls = val
        },
        update_btwrn(state, val) {
            state.btwrn = val
        },
        update_dkod(state, val) {
            state.dkod = val
        },
        update_dkos(state, val) {
            state.dkos = val
        },
        update_tkod(state, val) {
            state.tkod = val
        },
        update_tkos(state, val) {
            state.tkos = val
        },
        update_value_bb(state, val) {
            state.value_bb = val
        },
        update_value_bf(state, val) {
            state.value_bf = val
        },
        update_value_tb(state, val) {
            state.value_tb = val
        },
        update_location_id(state, val) {
            state.location_id = val
        },
        update_station_id(state, val) {
            state.station_id = val
        },
        update_status_done(state, val) {
            state.status_done = val
        },
        update_dialog_done(state, val) {
            state.dialog_done = val
        },
        update_url_labnumber(state, val) {
            state.url_labnumber = val
        },
        update_isdone(state, val) {
            state.isdone = val
        },
        update_get_data_status(state, val) {
            state.get_data_status = val
        },
        update_locations(state, val) {
            state.locations = val
        },
        update_selected_location(state, val) {
            state.selected_location = val
        },
        update_stations(state, val) {
            state.stations = val
        },
        update_selected_station(state, val) {
            state.selected_station = val
        },
        update_html5QrCode(state, val) {
            state.html5QrCode = val
        },
        update_isScanning(state, val) {
            state.isScanning = val
        },
        update_alert_msg(state, val) {
            state.alert_msg = val
        },
        update_alert_status(state, val) {
            state.alert_status = val
        },
        update_act_scan(state, val) {
            state.act_scan = val
        },
        update_sampletypes(state, val) {
            state.sampletypes = val
        },
        update_selected_patient(state, val) {
            state.selected_patient = val
        },
        update_dialog_scanner(state, val) {
            state.dialog_scanner = val
        },
        update_data_sample_other(state, val) {
            state.data_sample_other = val
        },
        update_data_sample_electromedic(state, val) {
            state.data_sample_electromedic = val
        },
        update_data_sample_radiodiagnostic(state, val) {
            state.data_sample_radiodiagnostic = val
        },
        update_data_sample_lab_undone(state, val) {
            state.data_sample_lab_undone = val
        },
        update_data_sample_lab_done(state, val) {
            state.data_sample_lab_done = val
        },
        update_data_sample_lab(state, val) {
            state.data_sample_lab = val
        },
        update_data_tests(state, val) {
            state.data_tests = val
        },
        update_data_packet(state, val) {
            state.data_packet = val
        },
        update_data_patient(state, val) {
            state.data_patient = val
        },
        update_order_id(state, val) {
            state.order_id = val
        },
        update_noreg(state, val) {
            state.noreg = val
        },
        update_dialog_details(state, val) {
            state.dialog_details = val
        },
        update_companies(state, val) {
            state.companies = val
        },
        update_selected_company(state, val) {
            state.selected_company = val
        },
        update_dialog_barcode(state, val) {
            state.dialog_barcode = val
        },
        update_statuses(state, val) {
            state.statuses = val
        },
        update_total_patients_all(state, val) {
            state.total_patients_all = val
        },
        update_save_status(state, val) {
            state.save_status = val
        },
        update_save_error_message(state, val) {
            state.save_error_message = val
        },
        update_lookup_error_message(state, val) {
            state.lookup_error_message = val
        },
        update_lookup_status(state, status) {
            state.lookup_status = status
        },
        update_promise_dialog(state, val) {
            state.promise_dialog = val
        },
        update_promises(state, val) {
            state.promises = val
        },
        update_errors(state, val) {
            state.errors = val
        },
        update_lastidx(state, val) {
            state.lastidx = val
        },
        update_selected_filter(state, val) {
            state.selected_filter = val
        },
        update_filters(state, val) {
            state.filters = val
        },
        update_total_page(state, val) {
            state.total_page = val
        },

        update_total_patient(state, val) {
            state.total_patient = val
        },
        update_current_page(state, val) {
            state.current_page = val
        },
        update_search_error_message(state, patient) {
            state.search_error_message = patient
        },
        update_search_patient(state, patient) {
            state.search_patient = patient
        },
        update_patients(state, data) {
            state.patients = data
        },
        update_selected_patient(state, val) {
            state.selected_patient = val
        },
        update_start_date(state, val) {
            state.start_date = val
        },
        update_autocomplete_status(state, val) {
            state.autocomplete_status = val
        },
        update_end_date(state, val) {
            state.end_date = val
        },
        update_search(state, val) {
            state.search = val
        },
        update_selected_status(state, val) {
            state.selected_status = val
        },
        update_open_alert_no_pay(state, val) {
            state.open_alert_no_pay = val
        },
        update_msg_alert_no_pay(state, val) {
            state.msg_alert_no_pay = val
        },
        update_data_requirement(state, val) {
            state.data_requirement = val
        },
        update_withCorection(state, val) {
            state.withCorection = val
        },
        update_odSph(state, val) {
            state.odSph = val
        },
        update_odCyl(state, val) {
            state.odCyl = val
        },
        update_odX(state, val) {
            state.odX = val
        },
        update_osSph(state, val) {
            state.osSph = val
        },
        update_osCyl(state, val) {
            state.osCyl = val
        },
        update_osX(state, val) {
            state.osX = val
        },
        update_colorBlindNumber(state, val) {
            state.colorBlindNumber = val
        },
        update_visusAdd(state, val) {
            state.visusAdd = val
        },
        update_dialogError(state, val) {
            state.dialogError = val
        },
        update_errorMsg(state, val) {
            state.errorMsg = val
        },
    },
    actions: {
        async load_data(context) {
            context.commit("update_search_patient", 1)
            try {
                //console.log(prm)
                let prm = {}
                prm.order_id = context.state.order_id
                prm.noreg = context.state.noreg
                let resp = await api.search_patient(prm)
                if (resp.status != "OK") {
                    context.commit("update_search_patient", 3)
                    context.commit("update_search_error_message", resp.message)
                    context.commit("update_errorMsg", resp.message)
                    context.commit("update_dialogError", true)
                } else {
                    context.commit("update_search_patient", 2)
                    context.commit("update_search_error_message", "")
                    let data_patient = resp.data.data_patient
                    let data_packet = resp.data.data_packet
                    let data_tests = resp.data.data_tests
                    let data_sample_lab = resp.data.data_sample_lab
                    let data_sample_radiodiagnostic = resp.data.data_sample_radiodiagnostic
                    let data_sample_electromedic = resp.data.data_sample_electromedic
                    let data_sample_other = resp.data.data_sample_other
                    let data_req = resp.data.data_requirement
                    console.log("Data req");
                    console.log(data_req);


                    context.commit("update_data_patient", data_patient)
                    context.commit("update_data_packet", data_packet)
                    context.commit("update_data_tests", data_tests)
                    context.commit("update_data_sample_lab", data_sample_lab)
                    context.commit("update_data_sample_radiodiagnostic", data_sample_radiodiagnostic)
                    context.commit("update_data_sample_electromedic", data_sample_electromedic)
                    context.commit("update_data_sample_other", data_sample_other)
                    context.commit("update_data_requirement", data_req)



                }
            } catch (e) {
                context.commit("update_search_patient", 3)
                context.commit("update_search_error_message", e.message)
                context.commit("update_errorMsg", e.message)
                context.commit("update_dialogError", true)
                console.log(e)
            }
        },
        async scan_patient(context, prm) {
            context.commit("update_search_patient", 1)
            context.commit("update_status_done", false)
            try {
                //console.log(prm)
                prm.token = one_token()
                //prm.station_id = context.state.selected_station.id
                //prm.location_id = context.state.selected_location.locationID
                let resp = await api.scan_patient(prm)
                if (resp.status != "OK") {
                    context.commit("update_search_patient", 3)
                    context.commit("update_search_error_message", resp.message)
                    context.commit("update_errorMsg", resp.message)
                    context.commit("update_dialogError", true)
                } else {
                    context.commit("update_search_patient", 2)
                    context.commit("update_search_error_message", "")
                    console.log("creturn")
                    console.log(_.isEmpty(resp.data.data_patient))

                    if (!_.isEmpty(resp.data.data_patient)) {
                        console.log('Y')
                        context.commit("update_data_patient", resp.data.data_patient)
                        context.commit("update_data_packet", resp.data.data_packet)
                        context.commit("update_data_tests", resp.data.data_tests)
                        context.commit("update_data_sample_lab", resp.data.data_sample_lab)
                        context.commit("update_data_sample_lab_undone", resp.data.data_sample_lab_undone)
                        context.commit("update_data_sample_lab_done", resp.data.data_sample_lab_done)
                        context.commit("update_data_requirement", resp.data.data_requirement)
                        context.commit("update_glucoses",resp.data.data_glucoses)

                        if (resp.data.data_sample_lab_undone.length === 0 && resp.data.data_sample_lab_done.length === resp.data.data_sample_lab.length) {
                            context.commit("update_status_done", true)
                            context.commit("update_dialog_done", true)
                        }
                    }
                    else {
                        if (resp.data.status && resp.data.status === "NOTCAL") {
                            context.commit("update_dialog_scanner", false)
                            context.commit("update_alert_msg", "Pasien dengan nomor lab " + prm.labnumber + " masih di station " + resp.data.data.T_SampleStationName)
                            context.commit("update_alert_status", true)
                        } else {
                            console.log('N')
                            context.commit("update_dialog_scanner", false)
                            context.commit("update_alert_msg", "Pasien dengan nomor lab " + prm.labnumber + " tidak ditemukan di station dan lokasi yang dipilih!")
                            context.commit("update_alert_status", true)
                        }

                    }

                }
            } catch (e) {
                context.commit("update_search_patient", 3)
                context.commit("update_search_error_message", e.message)
                context.commit("update_errorMsg", e.message)
                context.commit("update_dialogError", true)
                console.log(e)
            }
        },
        async scan_barcode(context, prm) {
            context.commit("update_search_patient", 1)
            context.commit("update_status_done", false)
            try {
                //console.log(prm)
                prm.token = one_token()
                prm.station = context.state.selected_station
                prm.location_id = context.state.selected_location.locationID
                prm.patient = context.state.data_patient
                prm.value_bb = context.state.value_bb
                prm.value_bf = context.state.value_bf
                prm.value_tb = context.state.value_tb
                prm.tkod = context.state.tkod
                prm.tkos = context.state.tkos
                prm.dkod = context.state.dkod
                prm.dkos = context.state.dkos
                prm.btwrn = context.state.btwrn
                prm.withCorection = context.state.withCorection ? 'Y' : "N"
                prm.visusAdd = context.state.visusAdd
                prm.odSph = context.state.odSph
                prm.odCyl = context.state.odCyl
                prm.odX = context.state.odX
                prm.osSph = context.state.osSph
                prm.osCyl = context.state.osCyl
                prm.osX = context.state.osX
                prm.colorBlindNumber = context.state.colorBlindNumber
                prm.glucoses = context.state.glucoses
                // console.log(prm);
                // return;

                let resp = await api.scanbarcode(prm)
                if (resp.status != "OK") {
                    context.commit("update_search_patient", 3)
                    context.commit("update_search_error_message", resp.message)
                    context.commit("update_errorMsg", resp.message)
                    context.commit("update_dialogError", true)
                } else {
                    context.commit("update_search_patient", 2)
                    context.commit("update_search_error_message", "")
                    if (resp.data.status_log === "Y") {
                        console.log(resp.data)
                        if (resp.data.isdone === "N")
                            location.reload()
                        else
                            location.replace("/one-ui/" + context.state.urls.url_sampling + "?stat=" + prm.station.id + "&loc=" + prm.location_id)

                    }
                    else {
                        context.commit("update_alert_msg", "Barcode " + prm.barcode + " tidak ditemukan pada pasien ini, pastikan barcode yang discan sudah benar")
                        context.commit("update_alert_status", true)
                    }

                }
            } catch (e) {
                context.commit("update_search_patient", 3)
                context.commit("update_search_error_message", e.message)
                context.commit("update_errorMsg", e.message)
                context.commit("update_dialogError", true)
                console.log(e)
            }
        },
        async lookup_statuses(context, prm) {
            context.commit("update_lookup_status", 1)
            try {
                prm.token = one_token()
                let resp = await api.lookup_statuses(prm)
                if (resp.status != "OK") {
                    context.commit("update_lookup_status", 3)
                    context.commit("update_lookup_error_message", resp.message)
                    context.commit("update_errorMsg", resp.message)
                    context.commit("update_dialogError", true)
                } else {
                    context.commit("update_lookup_status", 2)
                    context.commit("update_lookup_error_message", "")
                    let data = {
                        records: resp.data.records,
                        total: resp.data.total
                    }
                    context.commit("update_statuses", data.records)
                    //context.commit("update_promise_dialog",true)
                }
            } catch (e) {
                context.commit("update_lookup_status", 3)
                context.commit("update_lookup_error_message", e.message)
                context.commit("update_errorMsg", e.message)
                context.commit("update_dialogError", true)
            }
        },
        async getdatabarcodes(context, prm) {
            context.commit("update_lookup_status", 1)
            try {
                prm.token = one_token()
                let resp = await api.getdatabarcodes(prm)
                if (resp.status != "OK") {
                    context.commit("update_lookup_status", 3)
                    context.commit("update_lookup_error_message", resp.message)
                    context.commit("update_errorMsg", resp.message)
                    context.commit("update_dialogError", true)
                } else {
                    context.commit("update_lookup_status", 2)
                    context.commit("update_lookup_error_message", "")
                    let data = {
                        records: resp.data.records,
                        total: resp.data.total
                    }
                    context.commit("update_barcodes", data.records)
                    context.commit("update_dialog_barcode", true)
                }
            } catch (e) {
                context.commit("update_lookup_status", 3)
                context.commit("update_lookup_error_message", e.message)
                context.commit("update_errorMsg", e.message)
                context.commit("update_dialogError", true)
            }
        },
        async serahkan(context, prm) {
            context.commit("update_save_status", 1)
            try {
                prm.token = one_token()
                let resp = await api.serahkan(prm)
                if (resp.status != "OK") {
                    context.commit("update_save_status", 3)
                    context.commit("update_save_error_message", resp.message)
                    context.commit("update_errorMsg", resp.message)
                    context.commit("update_dialogError", true)
                } else {
                    context.commit("update_save_status", 2)
                    context.commit("update_save_error_message", "")
                    let data = {
                        records: resp.data.records,
                        total: resp.data.total
                    }
                    var row_data = prm
                    row_data.status = 'D'
                    row_data.received_time = resp.data.records.received_time
                    var patients = context.state.patients
                    //console.log(patients)
                    //console.log(prm.last_idx)
                    //console.log(prm.last_detail_idx)
                    //console.log(patients.details)
                    patients[prm.spk_idx].details[prm.last_idx].details[prm.last_detail_idx] = row_data
                    context.commit("update_patients", patients)
                    context.commit("update_dialog_details", false)
                }
            } catch (e) {
                context.commit("update_save_status", 3)
                context.commit("update_save_error_message", e.message)
                context.commit("update_errorMsg", e.message)
                context.commit("update_dialogError", true)
                console.log(e)
            }
        },
        async searchcompany(context, prm) {
            context.commit("update_autocomplete_status", 1)
            try {
                let resp = await api.searchcompany(one_token(), prm)
                if (resp.status != "OK") {
                    context.commit("update_autocomplete_status", 3)
                } else {
                    context.commit("update_autocomplete_status", 2)
                    let data = {
                        records: resp.data.records,
                        total: resp.data.total
                    }
                    context.commit("update_companies", resp.data.records)

                }
            } catch (e) {
                context.commit("update_autocomplete_status", 3)
            }
        },
        async getstations(context) {
            context.commit("update_get_data_status", 1);
            try {

                let resp = await api.getstations(one_token());
                if (resp.status != "OK") {
                    context.commit("update_get_data_status", 3);
                    context.commit("update_errorMsg", resp.message)
                    context.commit("update_dialogError", true)
                } else {
                    context.commit("update_get_data_status", 2);
                    let data = {
                        records: resp.data.records,
                        total: resp.data.total,
                    };
                    context.commit("update_stations", data.records.stations)

                    context.commit("update_urls", resp.data.urls)
                    if (context.state.station_id !== -1) {
                        console.log('in station id')
                        console.log(context.state.station_id)

                        let xidx = _.findIndex(data.records.stations, function (o) { return o.id == context.state.station_id })
                        console.log(xidx)

                        context.commit("update_selected_station", data.records.stations[xidx])
                        context.commit("update_station_id", 0)
                        context.dispatch("getLocation", data.records.stations[xidx].id)
                    } else {
                        console.log('another way')
                        context.commit("update_selected_station", data.records.stations[0])
                        context.dispatch("getLocation", data.records.stations[0].id)
                    }



                }
            } catch (e) {
                console.log(e);
                context.commit("update_errorMsg", e.message)
                context.commit("update_dialogError", true)
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
                    context.commit("update_errorMsg", resp.message)
                    context.commit("update_dialogError", true)
                } else {
                    context.commit("update_get_data_status", 2);

                    context.commit("update_locations", resp.data.records);
                    console.log('location')
                    console.log(context.state.location_id)
                    if (context.state.location_id !== -1) {
                        console.log("location inter")
                        //console.log(context.state.location_id)
                        let xidx = _.findIndex(resp.data.records, function (o) { return parseInt(o.locationID) == parseInt(context.state.location_id) })
                        console.log(xidx)
                        if (xidx > -1) {
                            context.commit("update_selected_location", resp.data.records[xidx])
                        } else {
                            context.commit("update_selected_location", resp.data.records[0])
                        }

                        context.commit("update_location_id", 0)
                    } else
                        context.commit("update_selected_location", resp.data.records[0])


                }
            } catch (e) {
                console.log(e);
                context.commit("update_errorMsg", e.message)
                context.commit("update_dialogError", true)
                context.commit("update_get_data_status", 3);
            }
        },
        async skipaction(context) {
            context.commit("update_get_data_status", 1);
            try {
                let param = {
                    token: one_token(),
                    order_id: context.state.data_patient.xid,
                    location_id: context.state.selected_location,
                    station: context.state.selected_station
                }
                //param.station = context.state.selected_station
                let resp = await api.skipaction(param);
                if (resp.status != "OK") {
                    context.commit("update_get_data_status", 3);
                    context.commit("update_errorMsg", resp.message)
                    context.commit("update_dialogError", true)
                } else {
                    context.commit("update_get_data_status", 2);
                    let station = context.state.selected_station
                    let location = context.state.selected_location
                    //location.replace("/one-ui/test/vuex/cpone-sample-lab-mobile-v3/")
                    window.location = "/one-ui/" + context.state.urls.url_sampling + "?stat=" + station.id + "&loc=" + location.locationID

                }
            } catch (e) {
                console.log(e);
                context.commit("update_errorMsg", e.message)
                context.commit("update_dialogError", true)
                context.commit("update_get_data_status", 3);
            }
        },
    }
}
