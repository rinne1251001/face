/**
 * Human demo for browsers
 * @default Human Library
 * @summary https://github.com/vladmandic/human
 * @author https://github.com/vladmandic
 * @copyright https://github.com/vladmandic
 * @license MIT
 */

// CDNから直接ESモジュールとして読み込み
import * as H from 'https://unpkg.com/@vladmandic/human/dist/human.esm.js';

const width = 1920;

const humanConfig = { // 型注釈（: Partial<H.Config>）を削除
    debug: true,
    backend: 'webgl',
    modelBasePath: 'https://vladmandic.github.io/human-models/models/',
    filter: { enabled: true, equalization: false, flip: false },
    face: { enabled: true, detector: { rotation: false }, mesh: { enabled: true }, attention: { enabled: false }, iris: { enabled: true }, description: { enabled: true }, emotion: { enabled: true }, antispoof: { enabled: true }, liveness: { enabled: true } },
    body: { enabled: false },
    hand: { enabled: false },
    object: { enabled: false },
    segmentation: { enabled: false },
    gesture: { enabled: true },
};

const human = new H.Human(humanConfig);

human.env.perfadd = false;
human.draw.options.font = 'small-caps 18px "Lato"';
human.draw.options.lineHeight = 20;
human.draw.options.drawPoints = true;

const dom = { // 型キャスト（as HTML...）を削除
    video: document.getElementById('video'),
    canvas: document.getElementById('canvas'),
    log: document.getElementById('log'),
    fps: document.getElementById('status'),
    perf: document.getElementById('performance'),
};
const timestamp = { detect: 0, draw: 0, tensors: 0, start: 0 };
const fps = { detectFPS: 0, drawFPS: 0, frames: 0, averageMs: 0 };

const log = (...msg) => {
    dom.log.innerText += msg.join(' ') + '\n';
    console.log(...msg);
};
const status = (msg) => dom.fps.innerText = msg;
const perf = (msg) => dom.perf.innerText = 'tensors:' + human.tf.memory().numTensors.toString() + ' | performance: ' + JSON.stringify(msg).replace(/"|{|}/g, '').replace(/,/g, ' | ');

async function detectionLoop() {
    if (!dom.video.paused) {
        if (timestamp.start === 0) timestamp.start = human.now();
        await human.detect(dom.video);
        const tensors = human.tf.memory().numTensors;
        if (tensors - timestamp.tensors !== 0) log('allocated tensors:', tensors - timestamp.tensors);
        timestamp.tensors = tensors;
        fps.detectFPS = Math.round(1000 * 1000 / (human.now() - timestamp.detect)) / 1000;
        fps.frames++;
        fps.averageMs = Math.round(1000 * (human.now() - timestamp.start) / fps.frames) / 1000;
        if (fps.frames % 100 === 0 && !dom.video.paused) log('performance', { ...fps, tensors: timestamp.tensors });
    }
    timestamp.detect = human.now();
    requestAnimationFrame(detectionLoop);
}

async function drawLoop() {
    if (!dom.video.paused) {
        const interpolated = human.next(human.result);
        const processed = await human.image(dom.video);
        human.draw.canvas(processed.canvas, dom.canvas);

        const opt = { bodyLabels: `person confidence [score] and ${human.result?.body?.[0]?.keypoints.length} keypoints` };
        await human.draw.all(dom.canvas, interpolated, opt);
        perf(interpolated.performance);
    }
    const now = human.now();
    fps.drawFPS = Math.round(1000 * 1000 / (now - timestamp.draw)) / 1000;
    timestamp.draw = now;
    status(dom.video.paused ? 'paused' : `fps: ${fps.detectFPS.toFixed(1).padStart(5, ' ')} detect | ${fps.drawFPS.toFixed(1).padStart(5, ' ')} draw`);
    setTimeout(drawLoop, 30);
}

async function webCam() {
    const devices = await human.webcam.enumerate();
    const id = devices[0].deviceId;
    const webcamStatus = await human.webcam.start({ element: dom.video, crop: false, width, id });
    log(webcamStatus);
    dom.canvas.width = human.webcam.width;
    dom.canvas.height = human.webcam.height;
    dom.canvas.onclick = async () => {
        if (human.webcam.paused) await human.webcam.play();
        else human.webcam.pause();
    };
}

async function main() {
    log('human version:', human.version, '| tfjs version:', human.tf.version['tfjs-core']);
    log('platform:', human.env.platform, '| agent:', human.env.agent);
    status('loading...');
    await human.load();
    log('backend:', human.tf.getBackend(), '| available:', human.env.backends);
    log('models stats:', human.models.stats());
    log('models loaded:', human.models.loaded());
    log('environment', human.env);
    status('initializing...');
    await human.warmup();
    await webCam();
    await detectionLoop();
    await drawLoop();
}

window.onload = main;