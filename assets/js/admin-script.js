! function(n) {
    "use strict";
    n(document).ready(function() {
        t.ready()
    }), n(window).load(function() {
        t.load()
    });
    var t = window.$cov_Ncrts = {
        ready: function() {
            this.ncrts_site(), this.ncrts_c(), this.ncrts_g(), this.ncrts_t(), this.ncrts_full(), this.ncrts_slip()
        },
        load: function() {},
        ncrts_site: function() {
            n("select[name=covid_country]").on("change", function(t) {
                var e = "",
                    c = n(this).val();
                e = "[COVID19-WIDGET", c && (e += ' country="' + c + '" title_widget="' + c + '"'), e += ' confirmed_title="Confirmed" deaths_title="Deaths" recovered_title="Recovered"]', n("#covidsh").html(e)
            })
        },
        ncrts_c: function() {
            n("select[name=covid_country_line]").on("change", function(t) {
                var e = "",
                    c = n(this).val();
                e = "[COVID19-LINE", c && (e += ' country="' + c + '"'), e += ' confirmed_title="confirmed" deaths_title="deaths" recovered_title="recovered"]', n("#covidsh-line").html(e)
            })
        },
        ncrts_g: function() {
            n("select[name=covid_country_graph]").on("change", function(t) {
                var e = "",
                    c = n(this).val();
                e = "[COVID19-GRAPH", c && (e += ' country="' + c + '" title="' + c + '"'), e += ' confirmed_title="Confirmed" deaths_title="Deaths" recovered_title="Recovered"]', n("#covidsh-graph").html(e)
            })
        },
        ncrts_t: function() {
            n("select[name=covid_country_ticker]").on("change", function(t) {
                var e = "",
                    c = n(this).val();
                e = "[COVID19-TICKER", c && (e += ' country="' + c + '" ticker_title="' + c + '"'), e += ' style="vertical" confirmed_title="Confirmed" deaths_title="Deaths" recovered_title="Recovered"]', n("#covidsh-ticker").html(e)
            })
        },
        ncrts_full: function() {
            n("select[name=covid_country_full]").on("change", function(t) {
                var e = "",
                    c = n(this).val();
                e = "[COVID19-WIDGET", c && (e += ' country="' + c + '" title_widget="' + c + '"'), e += ' format="full" confirmed_title="Confirmed" deaths_title="Deaths" recovered_title="Recovered" active_title="Active" today_cases="24h" today_deaths="24h"]', n("#covidsh-full").html(e)
            })
        },
        ncrts_slip: function() {
            n("select[name=covid_country_slip]").on("change", function(t) {
                var e = "",
                    c = n(this).val();
                e = "[COVID19-SLIP", c && (e += ' country="' + c + '"'), e += ' covid_title="Coronavirus" confirmed_title="Confirmed" deaths_title="Deaths" recovered_title="Recovered" active_title="Active" today_title="24h" world_title="World"]', n("#covidsh-slip").html(e)
            })
        }
    }
}(jQuery);