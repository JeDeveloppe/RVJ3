import { Controller } from '@hotwired/stimulus';
import L from 'leaflet';
import 'leaflet.markercluster/dist/MarkerCluster.css';
import 'leaflet.markercluster/dist/MarkerCluster.Default.css';
import 'leaflet.markercluster';

//?Regroupe visuellement les marqueurs proches (nuage illisible sinon avec >1000 villes), et
//?les separe automatiquement au fur et a mesure du zoom. S'accroche a la carte Symfony UX Map
//?(Leaflet) via l'evenement "connect" qu'elle emet en exposant l'instance Leaflet brute.
export default class extends Controller {
    connect() {
        this.element.addEventListener('ux:map:connect', this.onMapConnect.bind(this));
    }

    onMapConnect(event) {
        const { map, markers } = event.detail;

        //?Symfony UX Map ne transmet pas le "extra" jusqu'au marqueur Leaflet reel : on
        //?recupere donc le nombre de livraisons via le titre du marqueur ("Ville (N)"), deja
        //?construit cote PHP, plutot que de dupliquer la donnee ailleurs.
        function countFor(marker) {
            const match = /\((\d+)\)$/.exec(marker.options.title || '');
            return match ? parseInt(match[1], 10) : 1;
        }

        const clusterGroup = L.markerClusterGroup({
            iconCreateFunction: function (cluster) {
                const total = cluster.getAllChildMarkers().reduce(function (sum, marker) {
                    return sum + countFor(marker);
                }, 0);

                let sizeClass = 'marker-cluster-small';
                if (total >= 500) {
                    sizeClass = 'marker-cluster-large';
                } else if (total >= 50) {
                    sizeClass = 'marker-cluster-medium';
                }

                return L.divIcon({
                    html: '<div><span>' + total + '</span></div>',
                    className: 'marker-cluster ' + sizeClass,
                    iconSize: L.point(40, 40),
                });
            },
        });

        markers.forEach(function (marker) {
            map.removeLayer(marker);
            clusterGroup.addLayer(marker);
        });

        map.addLayer(clusterGroup);
    }
}
