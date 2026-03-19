import * as THREE from 'https://unpkg.com/three@0.167.1/build/three.module.js';
let useRef;
let useEffect;
let _jsx;
const defaultColors = ['#5227FF', '#FF9FFC', '#B19EEF'];
function LiquidEther({ mouseForce = 20, cursorSize = 100, isViscous = false, viscous = 30, iterationsViscous = 32, iterationsPoisson = 32, dt = 0.014, BFECC = true, resolution = 0.5, isBounce = false, colors = defaultColors, color0, color1, color2, style = {}, className = '', autoDemo = true, autoSpeed = 0.5, autoIntensity = 2.2, takeoverDuration = 0.25, autoResumeDelay = 1000, autoRampDuration = 0.6, maxPixelRatio = 1.25, antialias = false, targetFps = 30 }) {
    const mountRef = useRef(null);
    const webglRef = useRef(null);
    const resizeObserverRef = useRef(null);
    const rafRef = useRef(null);
    const intersectionObserverRef = useRef(null);
    const isVisibleRef = useRef(true);
    const resizeRafRef = useRef(null);
    useEffect(() => {
        if (!mountRef.current)
            return;
        function makePaletteTexture(stops) {
            let arr;
            if (Array.isArray(stops) && stops.length > 0) {
                arr = stops.length === 1 ? [stops[0], stops[0]] : stops;
            }
            else {
                arr = ['#ffffff', '#ffffff'];
            }
            const w = arr.length;
            const data = new Uint8Array(w * 4);
            for (let i = 0; i < w; i++) {
                const c = new THREE.Color(arr[i]);
                data[i * 4 + 0] = Math.round(c.r * 255);
                data[i * 4 + 1] = Math.round(c.g * 255);
                data[i * 4 + 2] = Math.round(c.b * 255);
                data[i * 4 + 3] = 255;
            }
            const tex = new THREE.DataTexture(data, w, 1, THREE.RGBAFormat);
            tex.magFilter = THREE.LinearFilter;
            tex.minFilter = THREE.LinearFilter;
            tex.wrapS = THREE.ClampToEdgeWrapping;
            tex.wrapT = THREE.ClampToEdgeWrapping;
            tex.generateMipmaps = false;
            tex.needsUpdate = true;
            return tex;
        }
        const paletteStops = Array.isArray(colors) && colors.length > 0 ? colors : [color0, color1, color2].filter(Boolean);
        const paletteTex = makePaletteTexture(paletteStops);
        // Hard-code transparent background vector (alpha 0)
        const bgVec4 = new THREE.Vector4(0, 0, 0, 0);
        class CommonClass {
            constructor() {
                this.width = 0;
                this.height = 0;
                this.aspect = 1;
                this.pixelRatio = 1;
                this.isMobile = false;
                this.breakpoint = 768;
                this.fboWidth = null;
                this.fboHeight = null;
                this.time = 0;
                this.delta = 0;
                this.container = null;
                this.renderer = null;
                this.clock = null;
            }
            init(container) {
                this.container = container;
                if (this.container.id === 'liquid-ether-bg') {
                    this.container.style.position = 'fixed';
                    this.container.style.inset = '0';
                    this.container.style.width = '100vw';
                    this.container.style.height = '100vh';
                }
                this.pixelRatio = Math.min(window.devicePixelRatio || 1, Math.max(1, maxPixelRatio));
                this.resize();
                this.renderer = new THREE.WebGLRenderer({
                    antialias,
                    alpha: true,
                    depth: false,
                    stencil: false,
                    powerPreference: 'low-power',
                    precision: 'mediump'
                });
                // Always transparent
                this.renderer.autoClear = false;
                this.renderer.setClearColor(new THREE.Color(0x000000), 0);
                this.renderer.setPixelRatio(this.pixelRatio);
                this.renderer.setSize(this.width, this.height);
                const el = this.renderer.domElement;
                el.style.width = '100%';
                el.style.height = '100%';
                el.style.display = 'block';
                this.clock = new THREE.Clock();
                this.clock.start();
            }
            resize() {
                if (!this.container)
                    return;
                const rect = this.container.getBoundingClientRect();
                const viewportWidth = Math.max(1, Math.floor(window.innerWidth || rect.width));
                const viewportHeight = Math.max(1, Math.floor(window.innerHeight || rect.height));
                const rectWidth = Math.max(1, Math.floor(rect.width));
                const rectHeight = Math.max(1, Math.floor(rect.height));
                const isLoginBackground = this.container.id === 'liquid-ether-bg';
                this.width = isLoginBackground ? viewportWidth : rectWidth;
                this.height = isLoginBackground ? viewportHeight : rectHeight;
                this.aspect = this.width / this.height;
                if (this.renderer)
                    this.renderer.setSize(this.width, this.height, false);
            }
            update() {
                if (!this.clock)
                    return;
                this.delta = this.clock.getDelta();
                this.time += this.delta;
            }
        }
        const Common = new CommonClass();
        class MouseClass {
            constructor() {
                this.mouseMoved = false;
                this.coords = new THREE.Vector2();
                this.coords_old = new THREE.Vector2();
                this.diff = new THREE.Vector2();
                this.timer = null;
                this.container = null;
                this.docTarget = null;
                this.listenerTarget = null;
                this.isHoverInside = false;
                this.hasUserControl = false;
                this.isAutoActive = false;
                this.autoIntensity = 2.0;
                this.takeoverActive = false;
                this.takeoverStartTime = 0;
                this.takeoverDuration = 0.25;
                this.takeoverFrom = new THREE.Vector2();
                this.takeoverTo = new THREE.Vector2();
                this.onInteract = null;
                this._onMouseMove = this.onDocumentMouseMove.bind(this);
                this._onTouchStart = this.onDocumentTouchStart.bind(this);
                this._onTouchMove = this.onDocumentTouchMove.bind(this);
                this._onTouchEnd = this.onTouchEnd.bind(this);
                this._onDocumentLeave = this.onDocumentLeave.bind(this);
            }
            init(container) {
                this.container = container;
                this.docTarget = container.ownerDocument || null;
                const defaultView = this.docTarget?.defaultView || (typeof window !== 'undefined' ? window : null);
                if (!defaultView)
                    return;
                this.listenerTarget = defaultView;
                this.listenerTarget.addEventListener('mousemove', this._onMouseMove);
                this.listenerTarget.addEventListener('touchstart', this._onTouchStart, {
                    passive: true
                });
                this.listenerTarget.addEventListener('touchmove', this._onTouchMove, {
                    passive: true
                });
                this.listenerTarget.addEventListener('touchend', this._onTouchEnd);
                this.docTarget?.addEventListener('mouseleave', this._onDocumentLeave);
            }
            dispose() {
                if (this.listenerTarget) {
                    this.listenerTarget.removeEventListener('mousemove', this._onMouseMove);
                    this.listenerTarget.removeEventListener('touchstart', this._onTouchStart);
                    this.listenerTarget.removeEventListener('touchmove', this._onTouchMove);
                    this.listenerTarget.removeEventListener('touchend', this._onTouchEnd);
                }
                if (this.docTarget) {
                    this.docTarget.removeEventListener('mouseleave', this._onDocumentLeave);
                }
                this.listenerTarget = null;
                this.docTarget = null;
                this.container = null;
            }
            isPointInside(clientX, clientY) {
                if (!this.container)
                    return false;
                const rect = this.container.getBoundingClientRect();
                if (rect.width === 0 || rect.height === 0)
                    return false;
                return clientX >= rect.left && clientX <= rect.right && clientY >= rect.top && clientY <= rect.bottom;
            }
            updateHoverState(clientX, clientY) {
                this.isHoverInside = this.isPointInside(clientX, clientY);
                return this.isHoverInside;
            }
            setCoords(x, y) {
                if (!this.container)
                    return;
                if (this.timer)
                    window.clearTimeout(this.timer);
                const rect = this.container.getBoundingClientRect();
                if (rect.width === 0 || rect.height === 0)
                    return;
                const nx = (x - rect.left) / rect.width;
                const ny = (y - rect.top) / rect.height;
                this.coords.set(nx * 2 - 1, -(ny * 2 - 1));
                this.mouseMoved = true;
                this.timer = window.setTimeout(() => {
                    this.mouseMoved = false;
                }, 100);
            }
            setNormalized(nx, ny) {
                this.coords.set(nx, ny);
                this.mouseMoved = true;
            }
            onDocumentMouseMove(event) {
                if (!this.updateHoverState(event.clientX, event.clientY))
                    return;
                if (this.onInteract)
                    this.onInteract();
                if (this.isAutoActive && !this.hasUserControl && !this.takeoverActive) {
                    if (!this.container)
                        return;
                    const rect = this.container.getBoundingClientRect();
                    const nx = (event.clientX - rect.left) / rect.width;
                    const ny = (event.clientY - rect.top) / rect.height;
                    this.takeoverFrom.copy(this.coords);
                    this.takeoverTo.set(nx * 2 - 1, -(ny * 2 - 1));
                    this.takeoverStartTime = performance.now();
                    this.takeoverActive = true;
                    this.hasUserControl = true;
                    this.isAutoActive = false;
                    return;
                }
                this.setCoords(event.clientX, event.clientY);
                this.hasUserControl = true;
            }
            onDocumentTouchStart(event) {
                if (event.touches.length !== 1)
                    return;
                const t = event.touches[0];
                if (!this.updateHoverState(t.clientX, t.clientY))
                    return;
                if (this.onInteract)
                    this.onInteract();
                this.setCoords(t.clientX, t.clientY);
                this.hasUserControl = true;
            }
            onDocumentTouchMove(event) {
                if (event.touches.length !== 1)
                    return;
                const t = event.touches[0];
                if (!this.updateHoverState(t.clientX, t.clientY))
                    return;
                if (this.onInteract)
                    this.onInteract();
                this.setCoords(t.clientX, t.clientY);
            }
            onTouchEnd() {
                this.isHoverInside = false;
            }
            onDocumentLeave() {
                this.isHoverInside = false;
            }
            update() {
                if (this.takeoverActive) {
                    const t = (performance.now() - this.takeoverStartTime) / (this.takeoverDuration * 1000);
                    if (t >= 1) {
                        this.takeoverActive = false;
                        this.coords.copy(this.takeoverTo);
                        this.coords_old.copy(this.coords);
                        this.diff.set(0, 0);
                    }
                    else {
                        const k = t * t * (3 - 2 * t);
                        this.coords.copy(this.takeoverFrom).lerp(this.takeoverTo, k);
                    }
                }
                this.diff.subVectors(this.coords, this.coords_old);
                this.coords_old.copy(this.coords);
                if (this.coords_old.x === 0 && this.coords_old.y === 0)
                    this.diff.set(0, 0);
                if (this.isAutoActive && !this.takeoverActive)
                    this.diff.multiplyScalar(this.autoIntensity);
            }
        }
        const Mouse = new MouseClass();
        class AutoDriver {
            constructor(mouse, manager, opts) {
                this.active = false;
                this.current = new THREE.Vector2(0, 0);
                this.target = new THREE.Vector2();
                this.lastTime = performance.now();
                this.activationTime = 0;
                this.margin = 0.2;
                this._tmpDir = new THREE.Vector2();
                this.mouse = mouse;
                this.manager = manager;
                this.enabled = opts.enabled;
                this.speed = opts.speed;
                this.resumeDelay = opts.resumeDelay || 3000;
                this.rampDurationMs = (opts.rampDuration || 0) * 1000;
                this.pickNewTarget();
            }
            pickNewTarget() {
                const r = Math.random;
                this.target.set((r() * 2 - 1) * (1 - this.margin), (r() * 2 - 1) * (1 - this.margin));
            }
            forceStop() {
                this.active = false;
                this.mouse.isAutoActive = false;
            }
            update() {
                if (!this.enabled)
                    return;
                const now = performance.now();
                const idle = now - this.manager.lastUserInteraction;
                if (idle < this.resumeDelay) {
                    if (this.active)
                        this.forceStop();
                    return;
                }
                if (!this.active) {
                    this.active = true;
                    this.current.copy(this.mouse.coords);
                    this.lastTime = now;
                    this.activationTime = now;
                }
                if (!this.active)
                    return;
                this.mouse.isAutoActive = true;
                let dtSec = (now - this.lastTime) / 1000;
                this.lastTime = now;
                if (dtSec > 0.2)
                    dtSec = 0.016;
                const dir = this._tmpDir.subVectors(this.target, this.current);
                const dist = dir.length();
                if (dist < 0.01) {
                    this.pickNewTarget();
                    return;
                }
                dir.normalize();
                let ramp = 1;
                if (this.rampDurationMs > 0) {
                    const t = Math.min(1, (now - this.activationTime) / this.rampDurationMs);
                    ramp = t * t * (3 - 2 * t);
                }
                const step = this.speed * dtSec * ramp;
                const move = Math.min(step, dist);
                this.current.addScaledVector(dir, move);
                this.mouse.setNormalized(this.current.x, this.current.y);
            }
        }
        const face_vert = `
	attribute vec3 position;
	uniform vec2 px;
	uniform vec2 boundarySpace;
	varying vec2 uv;
	precision highp float;
	void main(){
	vec3 pos = position;
	vec2 scale = 1.0 - boundarySpace * 2.0;
	pos.xy = pos.xy * scale;
	uv = vec2(0.5)+(pos.xy)*0.5;
	gl_Position = vec4(pos, 1.0);
}
`;
        const line_vert = `
	attribute vec3 position;
	uniform vec2 px;
	precision highp float;
	varying vec2 uv;
	void main(){
	vec3 pos = position;
	uv = 0.5 + pos.xy * 0.5;
	vec2 n = sign(pos.xy);
	pos.xy = abs(pos.xy) - px * 1.0;
	pos.xy *= n;
	gl_Position = vec4(pos, 1.0);
}
`;
        const mouse_vert = `
		precision highp float;
		attribute vec3 position;
		attribute vec2 uv;
		uniform vec2 center;
		uniform vec2 scale;
		uniform vec2 px;
		varying vec2 vUv;
		void main(){
		vec2 pos = position.xy * scale * 2.0 * px + center;
		vUv = uv;
		gl_Position = vec4(pos, 0.0, 1.0);
}
`;
        const advection_frag = `
		precision highp float;
		uniform sampler2D velocity;
		uniform float dt;
		uniform bool isBFECC;
		uniform vec2 fboSize;
		uniform vec2 px;
		varying vec2 uv;
		void main(){
		vec2 ratio = max(fboSize.x, fboSize.y) / fboSize;
		if(isBFECC == false){
				vec2 vel = texture2D(velocity, uv).xy;
				vec2 uv2 = uv - vel * dt * ratio;
				vec2 newVel = texture2D(velocity, uv2).xy;
				gl_FragColor = vec4(newVel, 0.0, 0.0);
		} else {
				vec2 spot_new = uv;
				vec2 vel_old = texture2D(velocity, uv).xy;
				vec2 spot_old = spot_new - vel_old * dt * ratio;
				vec2 vel_new1 = texture2D(velocity, spot_old).xy;
				vec2 spot_new2 = spot_old + vel_new1 * dt * ratio;
				vec2 error = spot_new2 - spot_new;
				vec2 spot_new3 = spot_new - error / 2.0;
				vec2 vel_2 = texture2D(velocity, spot_new3).xy;
				vec2 spot_old2 = spot_new3 - vel_2 * dt * ratio;
				vec2 newVel2 = texture2D(velocity, spot_old2).xy; 
				gl_FragColor = vec4(newVel2, 0.0, 0.0);
		}
}
`;
        const color_frag = `
		precision highp float;
		uniform sampler2D velocity;
		uniform sampler2D palette;
		uniform vec4 bgColor;
		varying vec2 uv;
		void main(){
		vec2 vel = texture2D(velocity, uv).xy;
		float lenv = clamp(length(vel), 0.0, 1.0);
		vec3 c = texture2D(palette, vec2(lenv, 0.5)).rgb;
		vec3 outRGB = mix(bgColor.rgb, c, lenv);
		float outA = mix(bgColor.a, 1.0, lenv);
		gl_FragColor = vec4(outRGB, outA);
}
`;
        const divergence_frag = `
		precision highp float;
		uniform sampler2D velocity;
		uniform float dt;
		uniform vec2 px;
		varying vec2 uv;
		void main(){
		float x0 = texture2D(velocity, uv-vec2(px.x, 0.0)).x;
		float x1 = texture2D(velocity, uv+vec2(px.x, 0.0)).x;
		float y0 = texture2D(velocity, uv-vec2(0.0, px.y)).y;
		float y1 = texture2D(velocity, uv+vec2(0.0, px.y)).y;
		float divergence = (x1 - x0 + y1 - y0) / 2.0;
		gl_FragColor = vec4(divergence / dt);
}
`;
        const externalForce_frag = `
		precision highp float;
		uniform vec2 force;
		uniform vec2 center;
		uniform vec2 scale;
		uniform vec2 px;
		varying vec2 vUv;
		void main(){
		vec2 circle = (vUv - 0.5) * 2.0;
		float d = 1.0 - min(length(circle), 1.0);
		d *= d;
		gl_FragColor = vec4(force * d, 0.0, 1.0);
}
`;
        const poisson_frag = `
		precision highp float;
		uniform sampler2D pressure;
		uniform sampler2D divergence;
		uniform vec2 px;
		varying vec2 uv;
		void main(){
		float p0 = texture2D(pressure, uv + vec2(px.x * 2.0, 0.0)).r;
		float p1 = texture2D(pressure, uv - vec2(px.x * 2.0, 0.0)).r;
		float p2 = texture2D(pressure, uv + vec2(0.0, px.y * 2.0)).r;
		float p3 = texture2D(pressure, uv - vec2(0.0, px.y * 2.0)).r;
		float div = texture2D(divergence, uv).r;
		float newP = (p0 + p1 + p2 + p3) / 4.0 - div;
		gl_FragColor = vec4(newP);
}
`;
        const pressure_frag = `
		precision highp float;
		uniform sampler2D pressure;
		uniform sampler2D velocity;
		uniform vec2 px;
		uniform float dt;
		varying vec2 uv;
		void main(){
		float step = 1.0;
		float p0 = texture2D(pressure, uv + vec2(px.x * step, 0.0)).r;
		float p1 = texture2D(pressure, uv - vec2(px.x * step, 0.0)).r;
		float p2 = texture2D(pressure, uv + vec2(0.0, px.y * step)).r;
		float p3 = texture2D(pressure, uv - vec2(0.0, px.y * step)).r;
		vec2 v = texture2D(velocity, uv).xy;
		vec2 gradP = vec2(p0 - p1, p2 - p3) * 0.5;
		v = v - gradP * dt;
		gl_FragColor = vec4(v, 0.0, 1.0);
}
`;
        const viscous_frag = `
		precision highp float;
		uniform sampler2D velocity;
		uniform sampler2D velocity_new;
		uniform float v;
		uniform vec2 px;
		uniform float dt;
		varying vec2 uv;
		void main(){
		vec2 old = texture2D(velocity, uv).xy;
		vec2 new0 = texture2D(velocity_new, uv + vec2(px.x * 2.0, 0.0)).xy;
		vec2 new1 = texture2D(velocity_new, uv - vec2(px.x * 2.0, 0.0)).xy;
		vec2 new2 = texture2D(velocity_new, uv + vec2(0.0, px.y * 2.0)).xy;
		vec2 new3 = texture2D(velocity_new, uv - vec2(0.0, px.y * 2.0)).xy;
		vec2 newv = 4.0 * old + v * dt * (new0 + new1 + new2 + new3);
		newv /= 4.0 * (1.0 + v * dt);
		gl_FragColor = vec4(newv, 0.0, 0.0);
}
`;
        class ShaderPass {
            constructor(props) {
                this.scene = null;
                this.camera = null;
                this.material = null;
                this.geometry = null;
                this.plane = null;
                this.props = props || {};
                this.uniforms = this.props.material?.uniforms;
            }
            init(..._args) {
                this.scene = new THREE.Scene();
                this.camera = new THREE.Camera();
                if (this.uniforms) {
                    this.material = new THREE.RawShaderMaterial(this.props.material);
                    this.geometry = new THREE.PlaneGeometry(2, 2);
                    this.plane = new THREE.Mesh(this.geometry, this.material);
                    this.scene.add(this.plane);
                }
            }
            update(..._args) {
                if (!Common.renderer || !this.scene || !this.camera)
                    return;
                Common.renderer.setRenderTarget(this.props.output || null);
                Common.renderer.render(this.scene, this.camera);
                Common.renderer.setRenderTarget(null);
            }
        }
        class Advection extends ShaderPass {
            constructor(simProps) {
                super({
                    material: {
                        vertexShader: face_vert,
                        fragmentShader: advection_frag,
                        uniforms: {
                            boundarySpace: { value: simProps.cellScale },
                            px: { value: simProps.cellScale },
                            fboSize: { value: simProps.fboSize },
                            velocity: { value: simProps.src.texture },
                            dt: { value: simProps.dt },
                            isBFECC: { value: true }
                        }
                    },
                    output: simProps.dst
                });
                this.uniforms = this.props.material.uniforms;
                this.init();
            }
            init() {
                super.init();
                this.createBoundary();
            }
            createBoundary() {
                const boundaryG = new THREE.BufferGeometry();
                const vertices_boundary = new Float32Array([
                    -1, -1, 0, -1, 1, 0, -1, 1, 0, 1, 1, 0, 1, 1, 0, 1, -1, 0, 1, -1, 0, -1, -1, 0
                ]);
                boundaryG.setAttribute('position', new THREE.BufferAttribute(vertices_boundary, 3));
                const boundaryM = new THREE.RawShaderMaterial({
                    vertexShader: line_vert,
                    fragmentShader: advection_frag,
                    uniforms: this.uniforms
                });
                this.line = new THREE.LineSegments(boundaryG, boundaryM);
                this.scene.add(this.line);
            }
            update(...args) {
                const { dt, isBounce, BFECC } = (args[0] || {});
                if (!this.uniforms)
                    return;
                if (typeof dt === 'number')
                    this.uniforms.dt.value = dt;
                if (typeof isBounce === 'boolean')
                    this.line.visible = isBounce;
                if (typeof BFECC === 'boolean')
                    this.uniforms.isBFECC.value = BFECC;
                super.update();
            }
        }
        class ExternalForce extends ShaderPass {
            constructor(simProps) {
                super({ output: simProps.dst });
                this.init(simProps);
            }
            init(simProps) {
                super.init();
                const mouseG = new THREE.PlaneGeometry(1, 1);
                const mouseM = new THREE.RawShaderMaterial({
                    vertexShader: mouse_vert,
                    fragmentShader: externalForce_frag,
                    blending: THREE.AdditiveBlending,
                    depthWrite: false,
                    uniforms: {
                        px: { value: simProps.cellScale },
                        force: { value: new THREE.Vector2(0, 0) },
                        center: { value: new THREE.Vector2(0, 0) },
                        scale: { value: new THREE.Vector2(simProps.cursor_size, simProps.cursor_size) }
                    }
                });
                this.mouse = new THREE.Mesh(mouseG, mouseM);
                this.scene.add(this.mouse);
            }
            update(...args) {
                const props = args[0] || {};
                const forceX = (Mouse.diff.x / 2) * (props.mouse_force || 0);
                const forceY = (Mouse.diff.y / 2) * (props.mouse_force || 0);
                const cellScale = props.cellScale || { x: 1, y: 1 };
                const cursorSize = props.cursor_size || 0;
                const cursorSizeX = cursorSize * cellScale.x;
                const cursorSizeY = cursorSize * cellScale.y;
                const centerX = Math.min(Math.max(Mouse.coords.x, -1 + cursorSizeX + cellScale.x * 2), 1 - cursorSizeX - cellScale.x * 2);
                const centerY = Math.min(Math.max(Mouse.coords.y, -1 + cursorSizeY + cellScale.y * 2), 1 - cursorSizeY - cellScale.y * 2);
                const uniforms = this.mouse.material.uniforms;
                uniforms.force.value.set(forceX, forceY);
                uniforms.center.value.set(centerX, centerY);
                uniforms.scale.value.set(cursorSize, cursorSize);
                super.update();
            }
        }
        class Viscous extends ShaderPass {
            constructor(simProps) {
                super({
                    material: {
                        vertexShader: face_vert,
                        fragmentShader: viscous_frag,
                        uniforms: {
                            boundarySpace: { value: simProps.boundarySpace },
                            velocity: { value: simProps.src.texture },
                            velocity_new: { value: simProps.dst_.texture },
                            v: { value: simProps.viscous },
                            px: { value: simProps.cellScale },
                            dt: { value: simProps.dt }
                        }
                    },
                    output: simProps.dst,
                    output0: simProps.dst_,
                    output1: simProps.dst
                });
                this.init();
            }
            update(...args) {
                const { viscous, iterations, dt } = (args[0] || {});
                if (!this.uniforms)
                    return;
                let fbo_in, fbo_out;
                if (typeof viscous === 'number')
                    this.uniforms.v.value = viscous;
                const iter = iterations ?? 0;
                for (let i = 0; i < iter; i++) {
                    if (i % 2 === 0) {
                        fbo_in = this.props.output0;
                        fbo_out = this.props.output1;
                    }
                    else {
                        fbo_in = this.props.output1;
                        fbo_out = this.props.output0;
                    }
                    this.uniforms.velocity_new.value = fbo_in.texture;
                    this.props.output = fbo_out;
                    if (typeof dt === 'number')
                        this.uniforms.dt.value = dt;
                    super.update();
                }
                return fbo_out;
            }
        }
        class Divergence extends ShaderPass {
            constructor(simProps) {
                super({
                    material: {
                        vertexShader: face_vert,
                        fragmentShader: divergence_frag,
                        uniforms: {
                            boundarySpace: { value: simProps.boundarySpace },
                            velocity: { value: simProps.src.texture },
                            px: { value: simProps.cellScale },
                            dt: { value: simProps.dt }
                        }
                    },
                    output: simProps.dst
                });
                this.init();
            }
            update(...args) {
                const { vel } = (args[0] || {});
                if (this.uniforms && vel) {
                    this.uniforms.velocity.value = vel.texture;
                }
                super.update();
            }
        }
        class Poisson extends ShaderPass {
            constructor(simProps) {
                super({
                    material: {
                        vertexShader: face_vert,
                        fragmentShader: poisson_frag,
                        uniforms: {
                            boundarySpace: { value: simProps.boundarySpace },
                            pressure: { value: simProps.dst_.texture },
                            divergence: { value: simProps.src.texture },
                            px: { value: simProps.cellScale }
                        }
                    },
                    output: simProps.dst,
                    output0: simProps.dst_,
                    output1: simProps.dst
                });
                this.init();
            }
            update(...args) {
                const { iterations } = (args[0] || {});
                let p_in, p_out;
                const iter = iterations ?? 0;
                for (let i = 0; i < iter; i++) {
                    if (i % 2 === 0) {
                        p_in = this.props.output0;
                        p_out = this.props.output1;
                    }
                    else {
                        p_in = this.props.output1;
                        p_out = this.props.output0;
                    }
                    if (this.uniforms)
                        this.uniforms.pressure.value = p_in.texture;
                    this.props.output = p_out;
                    super.update();
                }
                return p_out;
            }
        }
        class Pressure extends ShaderPass {
            constructor(simProps) {
                super({
                    material: {
                        vertexShader: face_vert,
                        fragmentShader: pressure_frag,
                        uniforms: {
                            boundarySpace: { value: simProps.boundarySpace },
                            pressure: { value: simProps.src_p.texture },
                            velocity: { value: simProps.src_v.texture },
                            px: { value: simProps.cellScale },
                            dt: { value: simProps.dt }
                        }
                    },
                    output: simProps.dst
                });
                this.init();
            }
            update(...args) {
                const { vel, pressure } = (args[0] || {});
                if (this.uniforms && vel && pressure) {
                    this.uniforms.velocity.value = vel.texture;
                    this.uniforms.pressure.value = pressure.texture;
                }
                super.update();
            }
        }
        class Simulation {
            constructor(options) {
                this.fbos = {
                    vel_0: null,
                    vel_1: null,
                    vel_viscous0: null,
                    vel_viscous1: null,
                    div: null,
                    pressure_0: null,
                    pressure_1: null
                };
                this.fboSize = new THREE.Vector2();
                this.cellScale = new THREE.Vector2();
                this.boundarySpace = new THREE.Vector2();
                this.options = {
                    iterations_poisson: 18,
                    iterations_viscous: 18,
                    mouse_force: 16,
                    resolution: 0.4,
                    cursor_size: 100,
                    viscous: 24,
                    isBounce: false,
                    dt: 0.014,
                    isViscous: false,
                    BFECC: false,
                    ...options
                };
                this.init();
            }
            init() {
                this.calcSize();
                this.createAllFBO();
                this.createShaderPass();
            }
            getFloatType() {
                const isIOS = /(iPad|iPhone|iPod)/i.test(navigator.userAgent);
                return isIOS ? THREE.HalfFloatType : THREE.FloatType;
            }
            createAllFBO() {
                const type = this.getFloatType();
                const opts = {
                    type,
                    depthBuffer: false,
                    stencilBuffer: false,
                    minFilter: THREE.LinearFilter,
                    magFilter: THREE.LinearFilter,
                    wrapS: THREE.ClampToEdgeWrapping,
                    wrapT: THREE.ClampToEdgeWrapping
                };
                for (const key in this.fbos) {
                    this.fbos[key] = new THREE.WebGLRenderTarget(this.fboSize.x, this.fboSize.y, opts);
                }
            }
            createShaderPass() {
                this.advection = new Advection({
                    cellScale: this.cellScale,
                    fboSize: this.fboSize,
                    dt: this.options.dt,
                    src: this.fbos.vel_0,
                    dst: this.fbos.vel_1
                });
                this.externalForce = new ExternalForce({
                    cellScale: this.cellScale,
                    cursor_size: this.options.cursor_size,
                    dst: this.fbos.vel_1
                });
                this.viscous = new Viscous({
                    cellScale: this.cellScale,
                    boundarySpace: this.boundarySpace,
                    viscous: this.options.viscous,
                    src: this.fbos.vel_1,
                    dst: this.fbos.vel_viscous1,
                    dst_: this.fbos.vel_viscous0,
                    dt: this.options.dt
                });
                this.divergence = new Divergence({
                    cellScale: this.cellScale,
                    boundarySpace: this.boundarySpace,
                    src: this.fbos.vel_viscous0,
                    dst: this.fbos.div,
                    dt: this.options.dt
                });
                this.poisson = new Poisson({
                    cellScale: this.cellScale,
                    boundarySpace: this.boundarySpace,
                    src: this.fbos.div,
                    dst: this.fbos.pressure_1,
                    dst_: this.fbos.pressure_0
                });
                this.pressure = new Pressure({
                    cellScale: this.cellScale,
                    boundarySpace: this.boundarySpace,
                    src_p: this.fbos.pressure_0,
                    src_v: this.fbos.vel_viscous0,
                    dst: this.fbos.vel_0,
                    dt: this.options.dt
                });
            }
            calcSize() {
                const width = Math.max(1, Math.round(this.options.resolution * Common.width));
                const height = Math.max(1, Math.round(this.options.resolution * Common.height));
                this.cellScale.set(1 / width, 1 / height);
                this.fboSize.set(width, height);
            }
            resize() {
                this.calcSize();
                for (const key in this.fbos) {
                    this.fbos[key].setSize(this.fboSize.x, this.fboSize.y);
                }
            }
            update() {
                if (this.options.isBounce)
                    this.boundarySpace.set(0, 0);
                else
                    this.boundarySpace.copy(this.cellScale);
                this.advection.update({ dt: this.options.dt, isBounce: this.options.isBounce, BFECC: this.options.BFECC });
                this.externalForce.update({
                    cursor_size: this.options.cursor_size,
                    mouse_force: this.options.mouse_force,
                    cellScale: this.cellScale
                });
                let vel = this.fbos.vel_1;
                if (this.options.isViscous) {
                    vel = this.viscous.update({
                        viscous: this.options.viscous,
                        iterations: this.options.iterations_viscous,
                        dt: this.options.dt
                    });
                }
                this.divergence.update({ vel });
                const pressure = this.poisson.update({ iterations: this.options.iterations_poisson });
                this.pressure.update({ vel, pressure });
            }
        }
        class Output {
            constructor() {
                this.simulation = new Simulation();
                this.scene = new THREE.Scene();
                this.camera = new THREE.Camera();
                this.output = new THREE.Mesh(new THREE.PlaneGeometry(2, 2), new THREE.RawShaderMaterial({
                    vertexShader: face_vert,
                    fragmentShader: color_frag,
                    transparent: true,
                    depthWrite: false,
                    uniforms: {
                        velocity: { value: this.simulation.fbos.vel_0.texture },
                        boundarySpace: { value: new THREE.Vector2() },
                        palette: { value: paletteTex },
                        bgColor: { value: bgVec4 }
                    }
                }));
                this.scene.add(this.output);
            }
            resize() {
                this.simulation.resize();
            }
            render() {
                if (!Common.renderer)
                    return;
                Common.renderer.setRenderTarget(null);
                Common.renderer.render(this.scene, this.camera);
            }
            update() {
                this.simulation.update();
                this.render();
            }
        }
        class WebGLManager {
            constructor(props) {
                this.lastUserInteraction = performance.now();
                this.running = false;
                this.lastFrameTime = 0;
                this._loop = this.loop.bind(this);
                this._resize = this.resize.bind(this);
                this.props = props;
                this.setTargetFps(props.targetFps);
                Common.init(props.$wrapper);
                Mouse.init(props.$wrapper);
                Mouse.autoIntensity = props.autoIntensity;
                Mouse.takeoverDuration = props.takeoverDuration;
                Mouse.onInteract = () => {
                    this.lastUserInteraction = performance.now();
                    if (this.autoDriver)
                        this.autoDriver.forceStop();
                };
                this.autoDriver = new AutoDriver(Mouse, this, {
                    enabled: props.autoDemo,
                    speed: props.autoSpeed,
                    resumeDelay: props.autoResumeDelay,
                    rampDuration: props.autoRampDuration
                });
                this.init();
                window.addEventListener('resize', this._resize);
                this._onVisibility = () => {
                    const hidden = document.hidden;
                    if (hidden) {
                        this.pause();
                    }
                    else if (isVisibleRef.current) {
                        this.start();
                    }
                };
                document.addEventListener('visibilitychange', this._onVisibility);
            }
            init() {
                if (!Common.renderer)
                    return;
                this.props.$wrapper.prepend(Common.renderer.domElement);
                this.output = new Output();
            }
            resize() {
                Common.resize();
                this.output.resize();
            }
            render() {
                if (this.autoDriver)
                    this.autoDriver.update();
                Mouse.update();
                Common.update();
                this.output.update();
            }
            setTargetFps(fps) {
                const safeFps = Number.isFinite(fps) ? fps : 30;
                this.targetFps = Math.max(12, Math.min(60, safeFps));
                this.frameInterval = 1000 / this.targetFps;
            }
            loop(now = performance.now()) {
                if (!this.running)
                    return;
                if (!this.lastFrameTime)
                    this.lastFrameTime = now;
                if (now - this.lastFrameTime >= this.frameInterval) {
                    this.render();
                    this.lastFrameTime = now;
                }
                rafRef.current = requestAnimationFrame(this._loop);
            }
            start() {
                if (this.running)
                    return;
                this.running = true;
                this.lastFrameTime = 0;
                rafRef.current = requestAnimationFrame(this._loop);
            }
            pause() {
                this.running = false;
                if (rafRef.current) {
                    cancelAnimationFrame(rafRef.current);
                    rafRef.current = null;
                }
            }
            dispose() {
                try {
                    window.removeEventListener('resize', this._resize);
                    if (this._onVisibility)
                        document.removeEventListener('visibilitychange', this._onVisibility);
                    Mouse.dispose();
                    if (Common.renderer) {
                        const canvas = Common.renderer.domElement;
                        if (canvas && canvas.parentNode)
                            canvas.parentNode.removeChild(canvas);
                        Common.renderer.dispose();
                    }
                }
                catch {
                    /* noop */
                }
            }
        }
        const container = mountRef.current;
        container.style.position = container.style.position || 'relative';
        container.style.overflow = container.style.overflow || 'hidden';
        const webgl = new WebGLManager({
            $wrapper: container,
            autoDemo,
            autoSpeed,
            autoIntensity,
            takeoverDuration,
            autoResumeDelay,
            autoRampDuration,
            targetFps
        });
        webglRef.current = webgl;
        const applyOptionsFromProps = () => {
            if (!webglRef.current)
                return;
            const sim = webglRef.current.output?.simulation;
            if (!sim)
                return;
            const prevRes = sim.options.resolution;
            Object.assign(sim.options, {
                mouse_force: mouseForce,
                cursor_size: cursorSize,
                isViscous,
                viscous,
                iterations_viscous: iterationsViscous,
                iterations_poisson: iterationsPoisson,
                dt,
                BFECC,
                resolution,
                isBounce
            });
            if (resolution !== prevRes)
                sim.resize();
        };
        applyOptionsFromProps();
        webgl.start();
        const io = new IntersectionObserver(entries => {
            const entry = entries[0];
            const isVisible = entry.isIntersecting && entry.intersectionRatio > 0;
            isVisibleRef.current = isVisible;
            if (!webglRef.current)
                return;
            if (isVisible && !document.hidden) {
                webglRef.current.start();
            }
            else {
                webglRef.current.pause();
            }
        }, { threshold: [0, 0.01, 0.1] });
        io.observe(container);
        intersectionObserverRef.current = io;
        const ro = new ResizeObserver(() => {
            if (!webglRef.current)
                return;
            if (resizeRafRef.current)
                cancelAnimationFrame(resizeRafRef.current);
            resizeRafRef.current = requestAnimationFrame(() => {
                if (!webglRef.current)
                    return;
                webglRef.current.resize();
            });
        });
        ro.observe(container);
        resizeObserverRef.current = ro;
        return () => {
            if (rafRef.current)
                cancelAnimationFrame(rafRef.current);
            if (resizeObserverRef.current) {
                try {
                    resizeObserverRef.current.disconnect();
                }
                catch {
                    /* noop */
                }
            }
            if (intersectionObserverRef.current) {
                try {
                    intersectionObserverRef.current.disconnect();
                }
                catch {
                    /* noop */
                }
            }
            if (webglRef.current) {
                webglRef.current.dispose();
            }
            webglRef.current = null;
        };
    }, [
        BFECC,
        cursorSize,
        dt,
        isBounce,
        isViscous,
        iterationsPoisson,
        iterationsViscous,
        mouseForce,
        resolution,
        viscous,
        colors,
        color0,
        color1,
        color2,
        autoDemo,
        autoSpeed,
        autoIntensity,
        takeoverDuration,
        autoResumeDelay,
        autoRampDuration,
        maxPixelRatio,
        antialias,
        targetFps
    ]);
    useEffect(() => {
        const webgl = webglRef.current;
        if (!webgl)
            return;
        const sim = webgl.output?.simulation;
        if (!sim)
            return;
        const prevRes = sim.options.resolution;
        Object.assign(sim.options, {
            mouse_force: mouseForce,
            cursor_size: cursorSize,
            isViscous,
            viscous,
            iterations_viscous: iterationsViscous,
            iterations_poisson: iterationsPoisson,
            dt,
            BFECC,
            resolution,
            isBounce
        });
        if (webgl.autoDriver) {
            webgl.autoDriver.enabled = autoDemo;
            webgl.autoDriver.speed = autoSpeed;
            webgl.autoDriver.resumeDelay = autoResumeDelay;
            webgl.autoDriver.rampDurationMs = autoRampDuration * 1000;
            if (webgl.autoDriver.mouse) {
                webgl.autoDriver.mouse.autoIntensity = autoIntensity;
                webgl.autoDriver.mouse.takeoverDuration = takeoverDuration;
            }
        }
        if (typeof webgl.setTargetFps === 'function') {
            webgl.setTargetFps(targetFps);
        }
        if (resolution !== prevRes)
            sim.resize();
    }, [
        mouseForce,
        cursorSize,
        isViscous,
        viscous,
        iterationsViscous,
        iterationsPoisson,
        dt,
        BFECC,
        resolution,
        isBounce,
        autoDemo,
        autoSpeed,
        autoIntensity,
        takeoverDuration,
        autoResumeDelay,
        autoRampDuration,
        targetFps
    ]);
    return _jsx("div", { ref: mountRef, className: `liquid-ether-container ${className || ''}`, style: style });
}

export function mountLiquidEther(container, options = {}) {
  if (!container) return () => {};

  const effects = [];
  const cleanups = [];

  useRef = (initialValue) => ({ current: initialValue });
  useEffect = (effectFn) => {
    if (typeof effectFn === 'function') effects.push(effectFn);
  };
    _jsx = (_tag, props = {}) => {
    if (props.ref && typeof props.ref === 'object') {
      props.ref.current = container;
    }
    if (props.className) {
      container.className = props.className;
    }
    if (props.style && typeof props.style === 'object') {
      Object.assign(container.style, props.style);
    }
    return null;
  };

  LiquidEther(options);

  for (const effect of effects) {
    const cleanup = effect();
    if (typeof cleanup === 'function') cleanups.push(cleanup);
  }

  return () => {
    while (cleanups.length > 0) {
      const cleanup = cleanups.pop();
      try {
        cleanup();
      } catch {
        // noop
      }
    }
  };
}

function getLiquidEtherPerfProfile() {
  const cores = navigator.hardwareConcurrency || 4;
  const memory = navigator.deviceMemory || 4;
  const connection = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
  const saveData = !!(connection && connection.saveData);
  const veryLow = saveData || cores <= 2 || memory <= 2;
  const low = veryLow || cores <= 4 || memory <= 4;

  if (veryLow) {
    return {
      mouseForce: 12,
      cursorSize: 78,
      isViscous: false,
      viscous: 18,
      iterationsViscous: 8,
      iterationsPoisson: 10,
      dt: 0.014,
      BFECC: false,
      resolution: 0.26,
      isBounce: true,
      autoDemo: true,
      autoSpeed: 0.52,
      autoIntensity: 1.8,
      takeoverDuration: 0.2,
      autoResumeDelay: 260,
      autoRampDuration: 0.32,
      maxPixelRatio: 1,
      antialias: false,
      targetFps: 20
    };
  }

  if (low) {
    return {
      mouseForce: 14,
      cursorSize: 90,
      isViscous: true,
      viscous: 22,
      iterationsViscous: 12,
      iterationsPoisson: 14,
      dt: 0.014,
      BFECC: false,
      resolution: 0.34,
      isBounce: true,
      autoDemo: true,
      autoSpeed: 0.62,
      autoIntensity: 2.2,
      takeoverDuration: 0.2,
      autoResumeDelay: 320,
      autoRampDuration: 0.35,
      maxPixelRatio: 1,
      antialias: false,
      targetFps: 24
    };
  }

  return {
    mouseForce: 18,
    cursorSize: 104,
    isViscous: true,
    viscous: 28,
    iterationsViscous: 18,
    iterationsPoisson: 20,
    dt: 0.014,
    BFECC: true,
    resolution: 0.46,
    isBounce: true,
    autoDemo: true,
    autoSpeed: 0.82,
    autoIntensity: 2.8,
    takeoverDuration: 0.22,
    autoResumeDelay: 380,
    autoRampDuration: 0.4,
    maxPixelRatio: 1.25,
    antialias: false,
    targetFps: 30
  };
}

function readThemeColor(name, fallback) {
  const value = getComputedStyle(document.documentElement).getPropertyValue(name).trim();
  return value || fallback;
}

function readLoginAnimationMode() {
  const allowedModes = new Set([
    'liquid-ether',
    'aurora-flow',
    'particle-network',
    'neon-grid',
    'leather-upholstery',
    'glass-bubbles',
    'radar-rings',
    'diagonal-shimmer',
    'mosaic-pulse',
    'none'
  ]);
  const value = readThemeColor('--login-animation', 'liquid-ether')
    .replace(/["']/g, '')
    .trim()
    .toLowerCase();

  return allowedModes.has(value) ? value : 'liquid-ether';
}

function getLoginThemePalette() {
  return [
    readThemeColor('--bg-gradient-start', '#00c2ff'),
    readThemeColor('--azul-medio', '#38d9ff'),
    readThemeColor('--titulo-neon', '#7cecff'),
    readThemeColor('--naranja', '#b9f4ff'),
    readThemeColor('--bg-gradient-end', '#ffe5b8')
  ];
}

const colorProbeContext = document.createElement('canvas').getContext('2d');

function colorWithAlpha(color, alpha) {
  if (!colorProbeContext) {
    return color;
  }

  colorProbeContext.fillStyle = '#000000';
  colorProbeContext.fillStyle = color;
  const normalized = colorProbeContext.fillStyle;

  if (normalized.startsWith('#')) {
    let hex = normalized.slice(1);
    if (hex.length === 3) {
      hex = hex.split('').map(char => char + char).join('');
    }
    const intValue = Number.parseInt(hex, 16);
    const r = (intValue >> 16) & 255;
    const g = (intValue >> 8) & 255;
    const b = intValue & 255;
    return `rgba(${r}, ${g}, ${b}, ${alpha})`;
  }

  const parts = normalized.match(/[\d.]+/g);
  if (parts && parts.length >= 3) {
    const [r, g, b] = parts;
    return `rgba(${r}, ${g}, ${b}, ${alpha})`;
  }

  return color;
}

function prepareBackgroundContainer(container, className) {
  container.replaceChildren();
  container.className = className;
  container.style.position = 'fixed';
  container.style.inset = '0';
  container.style.width = '100vw';
  container.style.height = '100vh';
  container.style.pointerEvents = 'none';
  container.style.overflow = 'hidden';
}

function mountCanvasEffect(container, className, renderer, onResize = null) {
  prepareBackgroundContainer(container, className);

  const canvas = document.createElement('canvas');
  canvas.style.width = '100%';
  canvas.style.height = '100%';
  canvas.style.display = 'block';
  container.appendChild(canvas);

  const ctx = canvas.getContext('2d');
  if (!ctx) {
    return () => {
      container.replaceChildren();
    };
  }

  let width = 0;
  let height = 0;
  let dpr = 1;
  let frameId = 0;
  let running = true;

  const resize = () => {
    dpr = Math.min(window.devicePixelRatio || 1, 1.5);
    width = Math.max(1, container.clientWidth || window.innerWidth);
    height = Math.max(1, container.clientHeight || window.innerHeight);
    canvas.width = Math.floor(width * dpr);
    canvas.height = Math.floor(height * dpr);
    ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    if (typeof onResize === 'function') {
      onResize({ width, height, dpr, ctx });
    }
  };

  const loop = now => {
    if (!running) {
      return;
    }

    ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    renderer({ ctx, width, height, now });
    frameId = window.requestAnimationFrame(loop);
  };

  resize();
  window.addEventListener('resize', resize);
  frameId = window.requestAnimationFrame(loop);

  return () => {
    running = false;
    window.cancelAnimationFrame(frameId);
    window.removeEventListener('resize', resize);
    container.replaceChildren();
  };
}

function mountAuroraFlow(container, palette) {
  return mountCanvasEffect(
    container,
    'login-background aurora-flow-theme',
    ({ ctx, width, height, now }) => {
      const time = now * 0.00018;

      ctx.clearRect(0, 0, width, height);
      ctx.fillStyle = colorWithAlpha(palette[4], 0.08);
      ctx.fillRect(0, 0, width, height);

      ctx.globalCompositeOperation = 'screen';
      ctx.filter = 'blur(36px)';

      for (let index = 0; index < 4; index += 1) {
        const baseY = height * (0.2 + index * 0.16);
        const amplitude = height * (0.08 + index * 0.015);
        const wave = 0.004 + index * 0.0012;
        const gradient = ctx.createLinearGradient(0, baseY, width, baseY + amplitude);
        gradient.addColorStop(0, colorWithAlpha(palette[index % palette.length], 0));
        gradient.addColorStop(0.25, colorWithAlpha(palette[(index + 1) % palette.length], 0.22));
        gradient.addColorStop(0.7, colorWithAlpha(palette[(index + 2) % palette.length], 0.18));
        gradient.addColorStop(1, colorWithAlpha(palette[(index + 3) % palette.length], 0));

        ctx.fillStyle = gradient;
        ctx.beginPath();
        ctx.moveTo(0, height);
        for (let x = 0; x <= width + 40; x += 24) {
          const y =
            baseY +
            Math.sin(x * wave + time * (2.6 + index * 0.35)) * amplitude +
            Math.cos(x * wave * 0.4 + time * 1.6 + index) * amplitude * 0.35;
          ctx.lineTo(x, y);
        }
        ctx.lineTo(width, height);
        ctx.closePath();
        ctx.fill();
      }

      for (let orb = 0; orb < 5; orb += 1) {
        const radius = Math.max(width, height) * (0.12 + orb * 0.018);
        const centerX = width * (0.15 + orb * 0.19) + Math.sin(time * (0.9 + orb * 0.12)) * width * 0.08;
        const centerY = height * (0.25 + (orb % 3) * 0.2) + Math.cos(time * (1.1 + orb * 0.14)) * height * 0.1;
        const glow = ctx.createRadialGradient(centerX, centerY, 0, centerX, centerY, radius);
        glow.addColorStop(0, colorWithAlpha(palette[orb % palette.length], 0.18));
        glow.addColorStop(1, colorWithAlpha(palette[orb % palette.length], 0));
        ctx.fillStyle = glow;
        ctx.beginPath();
        ctx.arc(centerX, centerY, radius, 0, Math.PI * 2);
        ctx.fill();
      }

      ctx.filter = 'none';
      ctx.globalCompositeOperation = 'source-over';
    }
  );
}

function mountParticleNetwork(container, palette) {
  let particles = [];

  const reseedParticles = ({ width, height }) => {
    const total = Math.max(24, Math.min(60, Math.round((width * height) / 28000)));
    particles = Array.from({ length: total }, (_, index) => ({
      x: Math.random() * width,
      y: Math.random() * height,
      vx: (Math.random() - 0.5) * 0.45,
      vy: (Math.random() - 0.5) * 0.45,
      radius: 1.2 + Math.random() * 2.2,
      color: palette[index % palette.length]
    }));
  };

  return mountCanvasEffect(
    container,
    'login-background particle-network-theme',
    ({ ctx, width, height }) => {
      ctx.clearRect(0, 0, width, height);
      ctx.fillStyle = colorWithAlpha(palette[4], 0.05);
      ctx.fillRect(0, 0, width, height);

      particles.forEach(particle => {
        particle.x += particle.vx;
        particle.y += particle.vy;

        if (particle.x <= 0 || particle.x >= width) {
          particle.vx *= -1;
        }
        if (particle.y <= 0 || particle.y >= height) {
          particle.vy *= -1;
        }

        ctx.fillStyle = colorWithAlpha(particle.color, 0.9);
        ctx.beginPath();
        ctx.arc(particle.x, particle.y, particle.radius, 0, Math.PI * 2);
        ctx.fill();
      });

      const maxDistance = Math.min(190, Math.max(110, width * 0.14));
      for (let i = 0; i < particles.length; i += 1) {
        for (let j = i + 1; j < particles.length; j += 1) {
          const dx = particles[i].x - particles[j].x;
          const dy = particles[i].y - particles[j].y;
          const distance = Math.hypot(dx, dy);

          if (distance < maxDistance) {
            const alpha = (1 - distance / maxDistance) * 0.28;
            ctx.strokeStyle = colorWithAlpha(palette[(i + j) % palette.length], alpha);
            ctx.lineWidth = 1;
            ctx.beginPath();
            ctx.moveTo(particles[i].x, particles[i].y);
            ctx.lineTo(particles[j].x, particles[j].y);
            ctx.stroke();
          }
        }
      }
    },
    reseedParticles
  );
}

function mountNeonGrid(container, palette) {
  return mountCanvasEffect(
    container,
    'login-background neon-grid-theme',
    ({ ctx, width, height, now }) => {
      const spacing = Math.max(30, Math.min(60, width / 24));
      const offset = (now * 0.02) % spacing;
      const sweepX = (now * 0.12) % (width + 220) - 110;
      const sweepY = (now * 0.05) % (height + 160) - 80;

      ctx.clearRect(0, 0, width, height);
      ctx.fillStyle = colorWithAlpha(palette[4], 0.04);
      ctx.fillRect(0, 0, width, height);

      ctx.save();
      ctx.strokeStyle = colorWithAlpha(palette[2], 0.22);
      ctx.lineWidth = 1;

      for (let x = -spacing; x < width + spacing; x += spacing) {
        ctx.beginPath();
        ctx.moveTo(x + offset, 0);
        ctx.lineTo(x + offset, height);
        ctx.stroke();
      }

      for (let y = -spacing; y < height + spacing; y += spacing) {
        ctx.beginPath();
        ctx.moveTo(0, y + offset * 0.6);
        ctx.lineTo(width, y + offset * 0.6);
        ctx.stroke();
      }

      ctx.restore();

      const verticalSweep = ctx.createLinearGradient(sweepX - 90, 0, sweepX + 90, 0);
      verticalSweep.addColorStop(0, colorWithAlpha(palette[1], 0));
      verticalSweep.addColorStop(0.5, colorWithAlpha(palette[1], 0.18));
      verticalSweep.addColorStop(1, colorWithAlpha(palette[1], 0));
      ctx.fillStyle = verticalSweep;
      ctx.fillRect(sweepX - 90, 0, 180, height);

      const horizontalSweep = ctx.createLinearGradient(0, sweepY - 70, 0, sweepY + 70);
      horizontalSweep.addColorStop(0, colorWithAlpha(palette[3], 0));
      horizontalSweep.addColorStop(0.5, colorWithAlpha(palette[3], 0.14));
      horizontalSweep.addColorStop(1, colorWithAlpha(palette[3], 0));
      ctx.fillStyle = horizontalSweep;
      ctx.fillRect(0, sweepY - 70, width, 140);
    }
  );
}

function mountLeatherUpholstery(container, palette) {
  let leatherTexture = null;

  const buildLeatherTexture = ({ width, height }) => {
    const texture = document.createElement('canvas');
    const tw = Math.max(320, Math.round(width * 0.42));
    const th = Math.max(220, Math.round(height * 0.42));
    texture.width = tw;
    texture.height = th;
    const tctx = texture.getContext('2d');

    if (!tctx) {
      leatherTexture = null;
      return;
    }

    const base = tctx.createLinearGradient(0, 0, tw, th);
    base.addColorStop(0, '#bb6930');
    base.addColorStop(0.22, '#b35f2c');
    base.addColorStop(0.58, '#9f5125');
    base.addColorStop(1, '#71351a');
    tctx.fillStyle = base;
    tctx.fillRect(0, 0, tw, th);

    const warmLight = tctx.createRadialGradient(tw * 0.24, th * 0.18, 0, tw * 0.24, th * 0.18, tw * 0.7);
    warmLight.addColorStop(0, 'rgba(255, 205, 150, 0.22)');
    warmLight.addColorStop(1, 'rgba(255, 196, 132, 0)');
    tctx.fillStyle = warmLight;
    tctx.fillRect(0, 0, tw, th);

    const shade = tctx.createRadialGradient(tw * 0.82, th * 0.22, 0, tw * 0.82, th * 0.22, tw * 0.52);
    shade.addColorStop(0, 'rgba(47, 20, 8, 0.2)');
    shade.addColorStop(1, 'rgba(47, 20, 8, 0)');
    tctx.fillStyle = shade;
    tctx.fillRect(0, 0, tw, th);

    const cloudCount = Math.max(14, Math.round((tw * th) / 18000));
    for (let i = 0; i < cloudCount; i += 1) {
      const px = Math.random() * tw;
      const py = Math.random() * th;
      const rx = tw * (0.04 + Math.random() * 0.09);
      const ry = th * (0.035 + Math.random() * 0.07);
      const patch = tctx.createRadialGradient(px, py, 0, px, py, Math.max(rx, ry));
      const alpha = 0.035 + Math.random() * 0.05;
      const isWarm = Math.random() > 0.5;
      patch.addColorStop(0, isWarm ? `rgba(255, 192, 136, ${alpha})` : `rgba(70, 28, 12, ${alpha})`);
      patch.addColorStop(1, 'rgba(0, 0, 0, 0)');
      tctx.fillStyle = patch;
      tctx.beginPath();
      tctx.ellipse(px, py, rx, ry, Math.random() * Math.PI, 0, Math.PI * 2);
      tctx.fill();
    }

    const coarseGrainCount = Math.round((tw * th) / 18);
    for (let i = 0; i < coarseGrainCount; i += 1) {
      const x = Math.random() * tw;
      const y = Math.random() * th;
      const radius = 0.45 + Math.random() * 1.8;
      const alpha = 0.02 + Math.random() * 0.06;
      const bright = Math.random() > 0.58;
      tctx.fillStyle = bright
        ? `rgba(255, 214, 168, ${alpha})`
        : `rgba(66, 26, 11, ${alpha})`;
      tctx.beginPath();
      tctx.ellipse(
        x,
        y,
        radius * (0.45 + Math.random() * 1.7),
        radius * (0.3 + Math.random() * 1.2),
        Math.random() * Math.PI,
        0,
        Math.PI * 2
      );
      tctx.fill();
    }

    const fineGrainCount = Math.round((tw * th) / 8);
    for (let i = 0; i < fineGrainCount; i += 1) {
      const x = Math.random() * tw;
      const y = Math.random() * th;
      const radius = 0.18 + Math.random() * 0.65;
      tctx.fillStyle = Math.random() > 0.52
        ? `rgba(255, 225, 180, ${0.018 + Math.random() * 0.035})`
        : `rgba(54, 20, 8, ${0.018 + Math.random() * 0.03})`;
      tctx.beginPath();
      tctx.ellipse(
        x,
        y,
        radius * (0.5 + Math.random() * 0.8),
        radius * (0.35 + Math.random() * 0.55),
        Math.random() * Math.PI,
        0,
        Math.PI * 2
      );
      tctx.fill();
    }

    const creaseCount = Math.max(12, Math.round((tw + th) / 75));
    tctx.lineCap = 'round';
    for (let i = 0; i < creaseCount; i += 1) {
      const startX = Math.random() * tw;
      const startY = Math.random() * th;
      const length = tw * (0.16 + Math.random() * 0.34);
      const bend = th * (0.015 + Math.random() * 0.08);
      const endY = startY + (Math.random() - 0.5) * bend * 1.6;

      tctx.strokeStyle = `rgba(255, 214, 176, ${0.02 + Math.random() * 0.03})`;
      tctx.lineWidth = 0.5 + Math.random() * 1.2;
      tctx.beginPath();
      tctx.moveTo(startX, startY);
      tctx.bezierCurveTo(
        startX + length * (0.18 + Math.random() * 0.1),
        startY - bend,
        startX + length * (0.58 + Math.random() * 0.12),
        startY + bend,
        startX + length,
        endY
      );
      tctx.stroke();

      tctx.strokeStyle = `rgba(72, 30, 10, ${0.02 + Math.random() * 0.03})`;
      tctx.lineWidth *= 0.65;
      tctx.beginPath();
      tctx.moveTo(startX, startY + 0.8);
      tctx.bezierCurveTo(
        startX + length * 0.22,
        startY - bend + 0.8,
        startX + length * 0.68,
        startY + bend + 0.8,
        startX + length,
        endY + 0.8
      );
      tctx.stroke();
    }

    for (let i = 0; i < 22; i += 1) {
      const patchX = Math.random() * tw;
      const patchY = Math.random() * th;
      const patchR = tw * (0.045 + Math.random() * 0.1);
      const patch = tctx.createRadialGradient(patchX, patchY, 0, patchX, patchY, patchR);
      patch.addColorStop(0, `rgba(255, 208, 150, ${0.025 + Math.random() * 0.04})`);
      patch.addColorStop(1, 'rgba(255, 205, 148, 0)');
      tctx.fillStyle = patch;
      tctx.beginPath();
      tctx.arc(patchX, patchY, patchR, 0, Math.PI * 2);
      tctx.fill();
    }

    const vignette = tctx.createRadialGradient(tw * 0.48, th * 0.46, tw * 0.16, tw * 0.5, th * 0.5, tw * 0.82);
    vignette.addColorStop(0, 'rgba(0, 0, 0, 0)');
    vignette.addColorStop(1, 'rgba(26, 9, 0, 0.22)');
    tctx.fillStyle = vignette;
    tctx.fillRect(0, 0, tw, th);

    leatherTexture = texture;
  };

  return mountCanvasEffect(
    container,
    'login-background leather-upholstery-theme',
    ({ ctx, width, height, now }) => {
      ctx.clearRect(0, 0, width, height);
      if (leatherTexture) {
        ctx.drawImage(leatherTexture, 0, 0, width, height);
      }

      const sheenX = width * 0.08 + ((now * 0.028) % (width * 1.25));
      const sheen = ctx.createLinearGradient(sheenX - width * 0.12, 0, sheenX + width * 0.05, 0);
      sheen.addColorStop(0, 'rgba(255, 235, 210, 0)');
      sheen.addColorStop(0.45, 'rgba(255, 235, 210, 0.045)');
      sheen.addColorStop(0.6, 'rgba(255, 255, 255, 0.018)');
      sheen.addColorStop(1, 'rgba(255, 235, 210, 0)');
      ctx.fillStyle = sheen;
      ctx.fillRect(0, 0, width, height);

      const sideShade = ctx.createLinearGradient(0, 0, width, 0);
      sideShade.addColorStop(0, 'rgba(25, 10, 0, 0.2)');
      sideShade.addColorStop(0.18, 'rgba(25, 10, 0, 0)');
      sideShade.addColorStop(0.82, 'rgba(25, 10, 0, 0)');
      sideShade.addColorStop(1, 'rgba(18, 6, 0, 0.32)');
      ctx.fillStyle = sideShade;
      ctx.fillRect(0, 0, width, height);
    }
    ,
    buildLeatherTexture
  );
}

function mountGlassBubbles(container, palette) {
  let bubbles = [];

  const reseed = ({ width, height }) => {
    const total = Math.max(16, Math.min(34, Math.round((width * height) / 42000)));
    bubbles = Array.from({ length: total }, (_, index) => ({
      x: Math.random() * width,
      y: Math.random() * height,
      radius: Math.max(24, Math.min(88, 24 + Math.random() * 72)),
      speed: 0.12 + Math.random() * 0.45,
      drift: (Math.random() - 0.5) * 0.35,
      phase: Math.random() * Math.PI * 2,
      color: palette[index % palette.length]
    }));
  };

  return mountCanvasEffect(
    container,
    'login-background glass-bubbles-theme',
    ({ ctx, width, height, now }) => {
      const time = now * 0.001;
      ctx.clearRect(0, 0, width, height);
      ctx.fillStyle = colorWithAlpha(palette[4], 0.06);
      ctx.fillRect(0, 0, width, height);

      ctx.save();
      ctx.filter = 'blur(2px)';
      bubbles.forEach((bubble, index) => {
        bubble.y -= bubble.speed;
        bubble.x += Math.sin(time + bubble.phase) * bubble.drift;
        if (bubble.y < -bubble.radius * 1.2) {
          bubble.y = height + bubble.radius * 1.1;
          bubble.x = Math.random() * width;
        }

        const gradient = ctx.createRadialGradient(
          bubble.x - bubble.radius * 0.25,
          bubble.y - bubble.radius * 0.35,
          bubble.radius * 0.1,
          bubble.x,
          bubble.y,
          bubble.radius
        );
        gradient.addColorStop(0, colorWithAlpha('#ffffff', 0.22));
        gradient.addColorStop(0.25, colorWithAlpha(palette[(index + 1) % palette.length], 0.16));
        gradient.addColorStop(1, colorWithAlpha(bubble.color, 0.02));
        ctx.fillStyle = gradient;
        ctx.beginPath();
        ctx.arc(bubble.x, bubble.y, bubble.radius, 0, Math.PI * 2);
        ctx.fill();

        ctx.strokeStyle = colorWithAlpha('#ffffff', 0.18);
        ctx.lineWidth = 1;
        ctx.beginPath();
        ctx.arc(bubble.x, bubble.y, bubble.radius * 0.92, 0, Math.PI * 2);
        ctx.stroke();
      });
      ctx.restore();
    },
    reseed
  );
}

function mountRadarRings(container, palette) {
  return mountCanvasEffect(
    container,
    'login-background radar-rings-theme',
    ({ ctx, width, height, now }) => {
      const cx = width * 0.5;
      const cy = height * 0.5;
      const maxRadius = Math.hypot(width, height) * 0.55;
      const time = now * 0.001;

      ctx.clearRect(0, 0, width, height);
      const radial = ctx.createRadialGradient(cx, cy, 0, cx, cy, maxRadius);
      radial.addColorStop(0, colorWithAlpha(palette[0], 0.1));
      radial.addColorStop(0.45, colorWithAlpha(palette[1], 0.08));
      radial.addColorStop(1, colorWithAlpha(palette[4], 0.02));
      ctx.fillStyle = radial;
      ctx.fillRect(0, 0, width, height);

      ctx.strokeStyle = colorWithAlpha(palette[2], 0.14);
      ctx.lineWidth = 1;
      for (let ring = 0; ring < 8; ring += 1) {
        ctx.beginPath();
        ctx.arc(cx, cy, maxRadius * ((ring + 1) / 8), 0, Math.PI * 2);
        ctx.stroke();
      }

      ctx.strokeStyle = colorWithAlpha(palette[3], 0.16);
      ctx.beginPath();
      ctx.moveTo(cx, 0);
      ctx.lineTo(cx, height);
      ctx.stroke();
      ctx.beginPath();
      ctx.moveTo(0, cy);
      ctx.lineTo(width, cy);
      ctx.stroke();

      const sweepAngle = time * 0.7;
      const wedge = Math.PI / 5.5;
      const beam = ctx.createRadialGradient(cx, cy, 0, cx, cy, maxRadius);
      beam.addColorStop(0, colorWithAlpha(palette[4], 0.28));
      beam.addColorStop(1, colorWithAlpha(palette[4], 0));
      ctx.fillStyle = beam;
      ctx.beginPath();
      ctx.moveTo(cx, cy);
      ctx.arc(cx, cy, maxRadius, sweepAngle - wedge, sweepAngle + wedge);
      ctx.closePath();
      ctx.fill();

      for (let pulse = 0; pulse < 3; pulse += 1) {
        const progress = ((time * 0.24 + pulse * 0.33) % 1);
        const radius = maxRadius * progress;
        ctx.strokeStyle = colorWithAlpha(palette[pulse % palette.length], (1 - progress) * 0.35);
        ctx.lineWidth = 2;
        ctx.beginPath();
        ctx.arc(cx, cy, radius, 0, Math.PI * 2);
        ctx.stroke();
      }
    }
  );
}

function mountDiagonalShimmer(container, palette) {
  return mountCanvasEffect(
    container,
    'login-background diagonal-shimmer-theme',
    ({ ctx, width, height, now }) => {
      const spacing = Math.max(90, Math.min(160, width / 7));
      const travel = (now * 0.09) % (spacing * 2);
      ctx.clearRect(0, 0, width, height);

      const base = ctx.createLinearGradient(0, 0, width, height);
      base.addColorStop(0, colorWithAlpha(palette[0], 0.14));
      base.addColorStop(0.5, colorWithAlpha(palette[2], 0.08));
      base.addColorStop(1, colorWithAlpha(palette[4], 0.12));
      ctx.fillStyle = base;
      ctx.fillRect(0, 0, width, height);

      for (let offset = -height - spacing * 2; offset < width + height + spacing * 2; offset += spacing) {
        const shimmer = ctx.createLinearGradient(offset + travel, 0, offset + travel + spacing * 0.9, 0);
        shimmer.addColorStop(0, colorWithAlpha(palette[1], 0));
        shimmer.addColorStop(0.45, colorWithAlpha(palette[3], 0.18));
        shimmer.addColorStop(0.55, colorWithAlpha('#ffffff', 0.12));
        shimmer.addColorStop(1, colorWithAlpha(palette[1], 0));
        ctx.strokeStyle = shimmer;
        ctx.lineWidth = spacing * 0.38;
        ctx.beginPath();
        ctx.moveTo(offset + travel, height);
        ctx.lineTo(offset + height + travel, 0);
        ctx.stroke();
      }
    }
  );
}

function mountMosaicPulse(container, palette) {
  return mountCanvasEffect(
    container,
    'login-background mosaic-pulse-theme',
    ({ ctx, width, height, now }) => {
      const size = Math.max(34, Math.min(70, width / 18));
      const cols = Math.ceil(width / size) + 1;
      const rows = Math.ceil(height / size) + 1;
      const time = now * 0.0015;

      ctx.clearRect(0, 0, width, height);
      ctx.fillStyle = colorWithAlpha(palette[4], 0.08);
      ctx.fillRect(0, 0, width, height);

      for (let row = 0; row < rows; row += 1) {
        for (let col = 0; col < cols; col += 1) {
          const x = col * size;
          const y = row * size;
          const phase = time + row * 0.42 + col * 0.24;
          const alpha = 0.08 + ((Math.sin(phase) + 1) / 2) * 0.18;
          ctx.fillStyle = colorWithAlpha(palette[(row + col) % palette.length], alpha);
          ctx.fillRect(x, y, size - 2, size - 2);

          ctx.strokeStyle = colorWithAlpha('#ffffff', 0.04);
          ctx.lineWidth = 1;
          ctx.strokeRect(x + 0.5, y + 0.5, size - 3, size - 3);
        }
      }

      const glow = ctx.createRadialGradient(
        width * (0.5 + Math.sin(time * 0.45) * 0.18),
        height * (0.5 + Math.cos(time * 0.38) * 0.16),
        0,
        width * 0.5,
        height * 0.5,
        Math.max(width, height) * 0.48
      );
      glow.addColorStop(0, colorWithAlpha(palette[2], 0.18));
      glow.addColorStop(1, colorWithAlpha(palette[2], 0));
      ctx.fillStyle = glow;
      ctx.fillRect(0, 0, width, height);
    }
  );
}

function mountStaticBackground(container) {
  prepareBackgroundContainer(container, 'login-background static-theme');
  return () => {
    container.replaceChildren();
  };
}

function mountLiquidEtherTheme(container, palette, perf) {
  prepareBackgroundContainer(container, 'liquid-ether-container liquid-ether-theme');
  return mountLiquidEther(container, {
    ...perf,
    colors: palette,
    color0: palette[0],
    color1: palette[1],
    color2: palette[2],
    className: 'liquid-ether-container liquid-ether-theme',
    style: {
      position: 'fixed',
      inset: '0',
      width: '100vw',
      height: '100vh'
    }
  });
}

function mountLoginBackground(container, mode, palette, perf) {
  switch (mode) {
    case 'aurora-flow':
      return mountAuroraFlow(container, palette);
    case 'particle-network':
      return mountParticleNetwork(container, palette);
    case 'neon-grid':
      return mountNeonGrid(container, palette);
    case 'leather-upholstery':
      return mountLeatherUpholstery(container, palette);
    case 'glass-bubbles':
      return mountGlassBubbles(container, palette);
    case 'radar-rings':
      return mountRadarRings(container, palette);
    case 'diagonal-shimmer':
      return mountDiagonalShimmer(container, palette);
    case 'mosaic-pulse':
      return mountMosaicPulse(container, palette);
    case 'none':
      return mountStaticBackground(container);
    case 'liquid-ether':
    default:
      return mountLiquidEtherTheme(container, palette, perf);
  }
}

const liquidContainer = document.getElementById('liquid-ether-bg');
const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
const perf = getLiquidEtherPerfProfile();
const themePalette = getLoginThemePalette();
const animationMode = prefersReducedMotion ? 'none' : readLoginAnimationMode();

if (liquidContainer) {
  if (typeof window.__destroyLiquidEtherLogin === 'function') {
    window.__destroyLiquidEtherLogin();
  }
  window.__destroyLiquidEtherLogin = mountLoginBackground(liquidContainer, animationMode, themePalette, perf);
}
