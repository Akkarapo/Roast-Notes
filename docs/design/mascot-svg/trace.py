import vtracer, cv2, numpy as np, os, sys
from PIL import Image
S=sys.argv[1]; names=sys.argv[2:]
for n in names:
    f=S+('/mascot/pour-hd.png' if n=='pour' else f'/mascot/{n}.png')
    im=Image.open(f).convert('RGBA'); a0=np.array(im)
    small = n!='pour'
    k=max(1,round(1100/max(im.size)))
    m=a0[:,:,3]>100; px=a0[:,:,:3][m].astype(np.float32)
    K=6
    _,lab,cent=cv2.kmeans(px,K,None,(cv2.TERM_CRITERIA_EPS+cv2.TERM_CRITERIA_MAX_ITER,30,1.0),5,cv2.KMEANS_PP_CENTERS)
    lum=cent@np.array([.299,.587,.114]); cent2=cent.copy()
    for i in range(K):
        if lum[i]<50: cent2[i]=(20,20,26)
        elif cent[i].min()>215: cent2[i]=(244,240,226) if abs(cent[i][0]-cent[i][2])>8 else (250,250,248)
    cent2=cent2.astype(np.uint8)
    up=im.resize((im.width*k,im.height*k),Image.LANCZOS); a=np.array(up)
    al=cv2.GaussianBlur(a[:,:,3],(0,0),k*0.4); alm=(al>128).astype(np.uint8)
    if small:
        alm=cv2.erode(alm,cv2.getStructuringElement(cv2.MORPH_ELLIPSE,(k+1,k+1)))
        nl,cc,st,_=cv2.connectedComponentsWithStats(alm,8)
        big=st[1:,cv2.CC_STAT_AREA].max()
        keep=np.zeros(nl,bool); keep[1:]=st[1:,cv2.CC_STAT_AREA]>=big*0.04
        alm=keep[cc].astype(np.uint8)
    rgb=a[:,:,:3].astype(np.float32).reshape(-1,3)
    d=((rgb[:,None,:]-cent[None,:,:].astype(np.float32))**2).sum(2); li=d.argmin(1).reshape(a.shape[:2])
    oh=np.stack([cv2.GaussianBlur((li==j).astype(np.float32),(0,0),k*0.5) for j in range(K)],2); li=oh.argmax(2)
    out=np.zeros(a.shape,np.uint8); out[:,:,:3]=cent2[li]; out[:,:,3]=alm*255
    if small:
        ring=cv2.subtract(alm,cv2.erode(alm,cv2.getStructuringElement(cv2.MORPH_ELLIPSE,(int(k*1.6),int(k*1.6)))))
        out[ring>0,:3]=(20,20,26)
    Image.fromarray(out).save(f'{n}-q.png')
    vtracer.convert_image_to_svg_py(f'{n}-q.png', f'{n}.svg', colormode='color', hierarchical='stacked', mode='spline', filter_speckle=14, color_precision=8, layer_difference=8, corner_threshold=70, length_threshold=5.0, max_iterations=10, splice_threshold=45, path_precision=1)
    print(n, os.path.getsize(f'{n}.svg'))
