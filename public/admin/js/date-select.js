$(function () {
    var locale = {
        "format": 'YYYY-MM-DD',
        "separator": " - ",
        "applyLabel": "确定",
        "cancelLabel": "取消",
        "fromLabel": "起始时间",
        "toLabel": "结束时间'",
        "customRangeLabel": "自定义",
        "weekLabel": "W",
        "daysOfWeek": ["日", "一", "二", "三", "四", "五", "六"],
        "monthNames": ["一月", "二月", "三月", "四月", "五月", "六月", "七月", "八月", "九月", "十月", "十一月", "十二月"],
        "firstDay": 1
    };
    $('#demo').daterangepicker({
        'locale': locale,
        ranges: {
            '今日': [moment(), moment().add(1, 'days')],
            '昨日': [moment().subtract(1, 'days'), moment().subtract(0, 'days')],
            '最近7日': [moment().subtract(7, 'days'), moment()],
            '最近30日': [moment().subtract(30, 'days'), moment()],
            '本月': [moment().subtract(0, 'month').startOf('month'), moment().subtract(-1, 'month').startOf('month') ],
            '上月': [moment().subtract(1, 'month').startOf('month'), moment().subtract(0, 'month').startOf('month') ]
        },
        "alwaysShowCalendars": true,

        "opens": "right",
    }, function (start, end, label) {
        console.log('New date range selected: ' + start.format('YYYY-MM-DD') + ' to ' + end.format('YYYY-MM-DD') + ' (predefined range: ' + label + ')');
    });
})
