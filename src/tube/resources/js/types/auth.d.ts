export type Auth_page = {
        user: {
                id: number;
                name: string;
        }| null;
};
export type Auth = {
        id: number;
        profile_image: FileObject;
        profile_image_path: string;
        name: string;
        is_active: boolean;
} | null;