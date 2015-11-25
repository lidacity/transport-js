(function (doc, stops, routes) {
    'use strict';

    // элементы DOM
    var from = doc.getElementById('from');
    var to = doc.getElementById('to');

    // шаблон для вывода маршрута
    var tpl = {
        title: '_TITLE_',
        text: '_TEXT_',
        block: '_TEMPLATE_'
    };

    // заполнить указанный список значениями
    function fillOptions(opt) {
        var i, max;
        opt.length = 0;
        opt[0] = new Option('выберите остановку...', '');
        for (i = 0, max = stops.length; i < max; i += 1) {
            opt.add(new Option(stops[i], i));
        }
    }

    // вернуть массив подходящих маршрутов
    function search(stop1, stop2) {
        var i, numRoutes, numTrips;
        var st, i1, i2, list, t, time;
        var arr = [];

        // нужен массив данных по маршрутам
        if (typeof routes.length === 'undefined') return;

        // проверяем по очереди все маршруты :(
        for (i = 0, numRoutes = routes.length; i < numRoutes; i += 1) {
            // смотрим все остановки маршрута
            if (typeof routes[i].stops.length === 'undefined') continue;
            st = routes[i].stops;
            // ищем индекс начальной остановки
            i1 = 0;
            while (i1 < st.length) {
                if (st[i1] === stop1) {
                    break;
                }
                i1 += 1;
            }
            // если остановка не найдена или она последняя - маршрут не подходит
            if (i1 < st.length - 1) {
                // ищем индекс конечной остановки - с конца, так проще различить последнюю остановку
                i2 = st.length - 1;
                while (i2 > i1) {
                    if (st[i2] === stop2) {
                        break;
                    }
                    i2 -= 1;
                }
                // если остановка не найдена - маршрут не подходит
                if (i2 !== i1) {
                    // пройти по всем "строкам", собрать данные из найденных "столбцов"
                    list = [];
                    for (t = 0, numTrips = routes[i].trips.length; t < numTrips; t += 1) {
                        time = routes[i].time[t].split(','); //массив времени прибытия
                        if (time[i1].length && time[i2].length) {
                            list.push(time[i1] + ' - ' + time[i2] + ' ' + routes[i].trips[t]);
                        }
                    }
                    arr.push({
                        title: routes[i].title,
                        list: list
                    });
                }
            }
        }
        return arr;
    }

    // обновить результат после изменения выбора в списке
    function update() {
        var i1 = from.value;
        var i2 = to.value;
        var arr, i, n, s;
        if (i1 === i2) {
            show('same');
            return;
        }
        if (i1.length && i2.length) {
            arr = search(+i1, +i2);
            if (!arr.length) {
                show('empty');
                return;
            }
            s = '';
            for (i = 0, n = arr.length; i < n; i += 1) {
                s += template(arr[i]);
            }
            doc.getElementById('result').innerHTML = s;
            show('result');
        }
    }

    // поменять местами остановки "откуда" и "куда"
    function revert() {
        var i = from.selectedIndex;
        from.selectedIndex = to.selectedIndex;
        to.selectedIndex = i;
        update();
    }

    // показать указанный по id блок, скрыть остальные
    function show(id) {
        doc.getElementById('result').style.display = (id === 'result') ? 'block' : 'none';
        doc.getElementById('empty').style.display  = (id === 'empty')  ? 'block' : 'none';
        doc.getElementById('same').style.display   = (id === 'same')   ? 'block' : 'none';
    }

    // вывести карточку маршрута с отобранными рейсами
    function template(obj) {
        var s = tpl.block.replace(tpl.title, obj.title);
        s = s.replace(tpl.text, obj.list.join('<br>'));
        return s;
    }

    // заполнить списки остановок
    fillOptions(from);
    fillOptions(to);

    // привязать события к форме
    from.onchange = update;
    to.onchange = update;
    doc.getElementById('revert').onclick = revert;

    // публичные методы
    return {
        update: update,
        revert: revert
    };
})(
    this.document,
    _STOPS_,
    _ROUTES_
);
